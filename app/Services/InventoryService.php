<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Central inventory engine. All stock changes flow through this service
 * so the ledger stays complete, multi-store inventory (product_store) stays in sync,
 * and costing stays consistent.
 */
class InventoryService
{
    /**
     * Resolve the active store ID (explicit -> session -> user -> default -> first).
     */
    public function resolveStoreId(?int $storeId = null): int
    {
        if ($storeId && $storeId > 0) {
            return $storeId;
        }

        $sessionStore = session('admin_store_id');
        if ($sessionStore) {
            return (int) $sessionStore;
        }

        $userStore = auth()->user()?->store_id;
        if ($userStore) {
            return (int) $userStore;
        }

        $defaultStore = Store::where('is_default', true)->value('id');
        if ($defaultStore) {
            return (int) $defaultStore;
        }

        $firstStore = Store::value('id');
        if ($firstStore) {
            return (int) $firstStore;
        }

        return 1;
    }

    /**
     * Get stock quantity available for a specific store.
     */
    public function getStoreStock(Product $product, ?int $storeId = null): int
    {
        $resolvedStoreId = $this->resolveStoreId($storeId);

        $pivot = DB::table('product_store')
            ->where('product_id', $product->id)
            ->where('store_id', $resolvedStoreId)
            ->first();

        return $pivot ? (int) $pivot->current_quantity : 0;
    }

    /**
     * Record an inbound stock movement (increases stock).
     */
    public function inbound(
        Product $product,
        int $quantity,
        string $type,
        string $reference,
        ?float $unitCost = null,
        ?string $reason = null,
        ?string $documentType = null,
        ?int $documentId = null,
        ?int $userId = null,
        bool $preserveAverage = false,
        ?int $storeId = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        $resolvedStoreId = $this->resolveStoreId($storeId);

        $current = (int) $product->current_quantity;
        $prevAvg = (float) $product->average_cost;

        // Weighted average cost recalculation on inbound (product level).
        $newAvg = $prevAvg;
        if ($unitCost !== null && ! $preserveAverage) {
            $totalValue = ($prevAvg * $current) + ($unitCost * $quantity);
            $newQty = $current + $quantity;
            $newAvg = $newQty > 0 ? round($totalValue / $newQty, 2) : $prevAvg;

            if ($type === StockMovement::TYPE_OPENING && $current === 0) {
                $newAvg = round($unitCost, 2);
            }
        }

        // 1. Update global product aggregate
        $product->current_quantity = $current + $quantity;
        $product->average_cost = $newAvg;
        $product->save();

        // 2. Synchronize per-store inventory in product_store
        $storeBefore = (int) (DB::table('product_store')
            ->where('product_id', $product->id)
            ->where('store_id', $resolvedStoreId)
            ->value('current_quantity') ?? 0);

        $this->syncStoreInbound($product, $resolvedStoreId, $quantity, $unitCost, $preserveAverage);

        // 3. Record movement in ledger
        return $this->createMovement(
            product: $product,
            quantityChange: $quantity,
            type: $type,
            reference: $reference,
            prevQuantity: $storeBefore,
            newQuantity: $storeBefore + $quantity,
            unitCost: $unitCost,
            reason: $reason,
            documentType: $documentType,
            documentId: $documentId,
            userId: $userId,
            storeId: $resolvedStoreId,
        );
    }

    /**
     * Record an outbound stock movement (decreases stock).
     */
    public function outbound(
        Product $product,
        int $quantity,
        string $type,
        string $reference,
        ?string $reason = null,
        ?string $documentType = null,
        ?int $documentId = null,
        ?int $userId = null,
        bool $allowNegative = false,
        ?int $storeId = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        $resolvedStoreId = $this->resolveStoreId($storeId);

        // Check store-specific quantity
        $pivot = DB::table('product_store')
            ->where('product_id', $product->id)
            ->where('store_id', $resolvedStoreId)
            ->first();

        $storeCurrent = $pivot ? (int) $pivot->current_quantity : 0;
        $globalCurrent = (int) $product->current_quantity;

        // If product_store pivot row didn't exist yet, seed it from global stock
        if (! $pivot && $globalCurrent > 0) {
            $this->ensureStorePivot($product, $resolvedStoreId);
            $storeCurrent = $globalCurrent;
        }

        if (! $allowNegative && ($storeCurrent - $quantity) < 0) {
            $storeName = Store::where('id', $resolvedStoreId)->value('name') ?? 'Current Store';
            throw ValidationException::withMessages([
                'product' => "Insufficient stock for {$product->name} at {$storeName}. Only {$storeCurrent} unit(s) available.",
            ]);
        }

        // 1. Update global product aggregate
        $product->current_quantity = max(0, $globalCurrent - $quantity);
        $product->save();

        // 2. Update store pivot
        $newStoreQty = max(0, $storeCurrent - $quantity);
        $this->updateStoreQuantity($product->id, $resolvedStoreId, $newStoreQty);

        // 3. Record movement in ledger
        return $this->createMovement(
            product: $product,
            quantityChange: -$quantity,
            type: $type,
            reference: $reference,
            prevQuantity: $storeCurrent,
            newQuantity: $newStoreQty,
            unitCost: (float) $product->average_cost,
            reason: $reason,
            documentType: $documentType,
            documentId: $documentId,
            userId: $userId,
            storeId: $resolvedStoreId,
        );
    }

    /**
     * Undo the weighted-average effect of a previous inbound movement.
     *
     * Solves the inbound equation (avg * qty) = (prevAvg * prevQty) + (unitCost * qty)
     * for prevAvg so a voided receipt restores the cost the stock had beforehand.
     */
    public function reverseInboundCost(
        Product $product,
        int $quantity,
        float $unitCost,
        int $prevQuantity,
        ?int $storeId = null
    ): void {
        if ($quantity <= 0) {
            return;
        }

        $resolvedStoreId = $this->resolveStoreId($storeId);
        $currentQty = (int) $product->current_quantity;
        $currentAvg = (float) $product->average_cost;
        $remainingQty = $currentQty - $quantity;

        if ($prevQuantity <= 0 || $remainingQty <= 0) {
            return;
        }

        $remainingValue = ($currentAvg * $currentQty) - ($unitCost * $quantity);

        if ($remainingValue < 0) {
            return;
        }

        $restoredAvg = round($remainingValue / $remainingQty, 2);

        $product->average_cost = $restoredAvg;
        $product->save();

        DB::table('product_store')
            ->where('product_id', $product->id)
            ->where('store_id', $resolvedStoreId)
            ->update([
                'average_cost' => $restoredAvg,
                'updated_at' => now(),
            ]);
    }

    /**
     * Inter-store stock transfer: moves stock from source store to destination store atomically.
     */
    public function transferStock(
        Product $product,
        int $quantity,
        int $fromStoreId,
        int $toStoreId,
        int $userId,
        ?string $reason = null
    ): array {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Transfer quantity must be greater than zero.');
        }

        if ($fromStoreId === $toStoreId) {
            throw ValidationException::withMessages([
                'destination_store_id' => 'Destination store cannot be the same as the source store.',
            ]);
        }

        $sourcePivot = DB::table('product_store')
            ->where('product_id', $product->id)
            ->where('store_id', $fromStoreId)
            ->first();

        $sourceQty = $sourcePivot ? (int) $sourcePivot->current_quantity : 0;
        if ($sourceQty < $quantity) {
            $fromName = Store::where('id', $fromStoreId)->value('name') ?? "Store #{$fromStoreId}";
            throw ValidationException::withMessages([
                'quantity' => "Insufficient stock at {$fromName}. Available: {$sourceQty}, Requested: {$quantity}.",
            ]);
        }

        return DB::transaction(function () use ($product, $quantity, $fromStoreId, $toStoreId, $userId, $reason, $sourceQty) {
            $ref = 'TRF-'.strtoupper(bin2hex(random_bytes(4)));

            // 1. Deduct from source store
            $newSourceQty = $sourceQty - $quantity;
            $this->updateStoreQuantity($product->id, $fromStoreId, $newSourceQty);

            // 2. Add to destination store
            $destPivot = DB::table('product_store')
                ->where('product_id', $product->id)
                ->where('store_id', $toStoreId)
                ->first();

            $destQty = $destPivot ? (int) $destPivot->current_quantity : 0;
            $newDestQty = $destQty + $quantity;
            $this->updateStoreQuantity($product->id, $toStoreId, $newDestQty);

            // 3. Create transfer outbound movement
            $refIdOut = ReferenceGenerator::generate('stock_movement');
            $outMovement = StockMovement::create([
                'ref_id' => $refIdOut,
                'product_id' => $product->id,
                'store_id' => $fromStoreId,
                'from_store_id' => $fromStoreId,
                'to_store_id' => $toStoreId,
                'reference' => $ref,
                'type' => 'transfer_out',
                'quantity_change' => -$quantity,
                'prev_quantity' => $sourceQty,
                'new_quantity' => $newSourceQty,
                'unit_cost' => (float) $product->average_cost,
                'reason' => $reason ?? 'Transferred to '.(Store::where('id', $toStoreId)->value('name') ?? "Store #{$toStoreId}"),
                'user_id' => $userId,
                'movement_date' => now(),
            ]);

            // 4. Create transfer inbound movement
            $refIdIn = ReferenceGenerator::generate('stock_movement');
            $inMovement = StockMovement::create([
                'ref_id' => $refIdIn,
                'product_id' => $product->id,
                'store_id' => $toStoreId,
                'from_store_id' => $fromStoreId,
                'to_store_id' => $toStoreId,
                'reference' => $ref,
                'type' => 'transfer_in',
                'quantity_change' => $quantity,
                'prev_quantity' => $destQty,
                'new_quantity' => $newDestQty,
                'unit_cost' => (float) $product->average_cost,
                'reason' => $reason ?? 'Received from '.(Store::where('id', $fromStoreId)->value('name') ?? "Store #{$fromStoreId}"),
                'user_id' => $userId,
                'movement_date' => now(),
            ]);

            return [
                'reference' => $ref,
                'out_movement' => $outMovement,
                'in_movement' => $inMovement,
            ];
        });
    }

    /**
     * Set opening stock for a product (first-time initial stock).
     */
    public function setOpeningStock(
        Product $product,
        int $quantity,
        float $cost,
        ?int $userId = null,
        ?int $storeId = null
    ): StockMovement {
        return $this->inbound(
            product: $product,
            quantity: $quantity,
            type: StockMovement::TYPE_OPENING,
            reference: $product->sku.'|OPENING',
            unitCost: $cost,
            reason: 'Opening stock',
            documentType: 'product',
            documentId: $product->id,
            userId: $userId,
            storeId: $storeId,
        );
    }

    /**
     * Update store inbound stock and calculate weighted average per store.
     */
    protected function syncStoreInbound(
        Product $product,
        int $storeId,
        int $quantity,
        ?float $unitCost,
        bool $preserveAverage
    ): void {
        $pivot = DB::table('product_store')
            ->where('product_id', $product->id)
            ->where('store_id', $storeId)
            ->first();

        if ($pivot) {
            $prevQty = (int) $pivot->current_quantity;
            $prevAvg = (float) $pivot->average_cost;
            $newQty = $prevQty + $quantity;

            $newAvg = $prevAvg;
            if ($unitCost !== null && ! $preserveAverage) {
                $totalVal = ($prevAvg * $prevQty) + ($unitCost * $quantity);
                $newAvg = $newQty > 0 ? round($totalVal / $newQty, 2) : $prevAvg;
            }

            DB::table('product_store')
                ->where('id', $pivot->id)
                ->update([
                    'current_quantity' => $newQty,
                    'average_cost' => $newAvg,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('product_store')->insert([
                'product_id' => $product->id,
                'store_id' => $storeId,
                'current_quantity' => $quantity,
                'reorder_level' => $product->reorder_level ?? 0,
                'average_cost' => $unitCost ?? $product->average_cost ?? $product->cost_price ?? 0,
                'selling_price' => $product->selling_price,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Ensure a product_store pivot row exists.
     */
    public function ensureStorePivot(Product $product, int $storeId, int $quantity = 0): void
    {
        $exists = DB::table('product_store')
            ->where('product_id', $product->id)
            ->where('store_id', $storeId)
            ->exists();

        if (! $exists) {
            DB::table('product_store')->insert([
                'product_id' => $product->id,
                'store_id' => $storeId,
                'current_quantity' => $quantity,
                'reorder_level' => $product->reorder_level ?? 0,
                'average_cost' => $product->average_cost ?? $product->cost_price ?? 0,
                'selling_price' => $product->selling_price,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Update quantity for a store.
     */
    protected function updateStoreQuantity(int $productId, int $storeId, int $newQuantity): void
    {
        $updated = DB::table('product_store')
            ->where('product_id', $productId)
            ->where('store_id', $storeId)
            ->update([
                'current_quantity' => $newQuantity,
                'updated_at' => now(),
            ]);

        if (! $updated) {
            $product = Product::find($productId);
            DB::table('product_store')->insert([
                'product_id' => $productId,
                'store_id' => $storeId,
                'current_quantity' => $newQuantity,
                'reorder_level' => $product?->reorder_level ?? 0,
                'average_cost' => $product?->average_cost ?? $product?->cost_price ?? 0,
                'selling_price' => $product?->selling_price,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function createMovement(
        Product $product,
        int $quantityChange,
        string $type,
        string $reference,
        int $prevQuantity,
        int $newQuantity,
        ?float $unitCost = null,
        ?string $reason = null,
        ?string $documentType = null,
        ?int $documentId = null,
        ?int $userId = null,
        ?int $storeId = null
    ): StockMovement {
        $refId = ReferenceGenerator::generate('stock_movement');

        return StockMovement::create([
            'ref_id' => $refId,
            'product_id' => $product->id,
            'store_id' => $storeId ?? $this->resolveStoreId(),
            'reference' => $reference,
            'type' => $type,
            'quantity_change' => $quantityChange,
            'prev_quantity' => $prevQuantity,
            'new_quantity' => $newQuantity,
            'unit_cost' => $unitCost,
            'reason' => $reason,
            'user_id' => $userId,
            'document_type' => $documentType,
            'document_id' => $documentId,
            'movement_date' => now(),
        ]);
    }
}
