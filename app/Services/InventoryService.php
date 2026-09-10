<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Central inventory engine. All stock changes must flow through this service
 * so the ledger stays complete and costing stays consistent.
 */
class InventoryService
{
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
        bool $preserveAverage = false
    ): StockMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        $current = (int) $product->current_quantity;
        $prevAvg = (float) $product->average_cost;

        // Weighted average cost recalculation on inbound.
        $newAvg = $prevAvg;
        if ($unitCost !== null && ! $preserveAverage) {
            $totalValue = ($prevAvg * $current) + ($unitCost * $quantity);
            $newQty = $current + $quantity;
            $newAvg = $newQty > 0 ? round($totalValue / $newQty, 2) : $prevAvg;

            if ($type === StockMovement::TYPE_OPENING && $current === 0) {
                $newAvg = round($unitCost, 2);
            }
        }

        $product->current_quantity = $current + $quantity;
        $product->average_cost = $newAvg;
        $product->save();

        return $this->createMovement(
            product: $product,
            quantityChange: $quantity,
            type: $type,
            reference: $reference,
            prevQuantity: $current,
            newQuantity: $product->current_quantity,
            unitCost: $unitCost,
            reason: $reason,
            documentType: $documentType,
            documentId: $documentId,
            userId: $userId,
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
        bool $allowNegative = false
    ): StockMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        $current = (int) $product->current_quantity;

        if (!$allowNegative && ($current - $quantity) < 0) {
            throw ValidationException::withMessages([
                'product' => "Insufficient stock for {$product->name}. Only {$current} unit(s) available.",
            ]);
        }

        $product->current_quantity = $current - $quantity;

        if ($product->current_quantity < 0) {
            $product->current_quantity = 0;
        }

        $product->save();

        return $this->createMovement(
            product: $product,
            quantityChange: -$quantity,
            type: $type,
            reference: $reference,
            prevQuantity: $current,
            newQuantity: $product->current_quantity,
            unitCost: (float) $product->average_cost,
            reason: $reason,
            documentType: $documentType,
            documentId: $documentId,
            userId: $userId,
        );
    }

    /**
     * Set opening stock for a product (first-time initial stock).
     */
    public function setOpeningStock(
        Product $product,
        int $quantity,
        float $cost,
        ?int $userId = null
    ): StockMovement {
        return $this->inbound(
            product: $product,
            quantity: $quantity,
            type: StockMovement::TYPE_OPENING,
            reference: $product->sku . '|OPENING',
            unitCost: $cost,
            reason: 'Opening stock',
            documentType: 'product',
            documentId: $product->id,
            userId: $userId,
        );
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
        ?int $userId = null
    ): StockMovement {
        $refId = ReferenceGenerator::generate('stock_movement');

        return StockMovement::create([
            'ref_id' => $refId,
            'product_id' => $product->id,
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
