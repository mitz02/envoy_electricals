<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(protected InventoryService $inventory) {}

    /**
     * Create a purchase and automatically add quantities to inventory.
     *
     * @param  array  $items  Each: ['product_id' => int, 'quantity' => int, 'unit_cost' => float]
     */
    public function createPurchase(array $data, array $items, int $userId): Purchase
    {
        $storeId = (int) ($data['store_id']
            ?? session('admin_store_id')
            ?? auth()->user()?->store_id
            ?? Store::where('is_default', true)->value('id')
            ?? Store::value('id')
            ?? 1);

        return DB::transaction(function () use ($data, $items, $userId, $storeId) {
            $subtotal = 0;
            $preparedItems = [];

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $lineTotal = round((float) $item['unit_cost'] * (int) $item['quantity'], 2);
                $preparedItems[] = [
                    'product' => $product,
                    'quantity' => (int) $item['quantity'],
                    'unit_cost' => (float) $item['unit_cost'],
                    'line_total' => $lineTotal,
                ];
                $subtotal += $lineTotal;
            }

            $subtotal = round($subtotal, 2);
            $discount = round((float) ($data['discount'] ?? 0), 2);
            $total = round(max($subtotal - $discount, 0), 2);

            $amountPaid = round((float) ($data['amount_paid'] ?? 0), 2);
            if ($amountPaid > $total) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Payment cannot exceed purchase total.',
                ]);
            }
            $balance = round($total - $amountPaid, 2);

            $supplierId = null;

            if (! empty($data['supplier_id'])) {
                $supplierId = (int) $data['supplier_id'];
            } elseif (! empty($data['supplier_name'])) {
                $supplier = Supplier::create([
                    'ref_id' => ReferenceGenerator::generate('supplier'),
                    'name' => $data['supplier_name'],
                    'phone' => $data['supplier_phone'] ?? null,
                    'contact_person' => null,
                ]);
                $supplierId = $supplier->id;
            }

            $refId = ReferenceGenerator::generate('purchase');
            $purchase = Purchase::create([
                'ref_id' => $refId,
                'purchase_date' => $data['purchase_date'] ?? now()->toDateString(),
                'invoice_no' => $data['invoice_no'] ?? null,
                'store_id' => $storeId,
                'supplier_id' => $supplierId,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'amount_paid' => $amountPaid,
                'balance' => $balance,
                'payment_method' => $data['payment_method'] ?? null,
                'status' => 'completed',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($preparedItems as $pi) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $pi['product']->id,
                    'quantity' => $pi['quantity'],
                    'unit_cost' => $pi['unit_cost'],
                    'total' => $pi['line_total'],
                ]);

                $pi['product']->refresh();

                $this->inventory->inbound(
                    product: $pi['product'],
                    quantity: $pi['quantity'],
                    type: StockMovement::TYPE_PURCHASE,
                    reference: $refId,
                    unitCost: $pi['unit_cost'],
                    documentType: 'purchase',
                    documentId: $purchase->id,
                    userId: $userId,
                    storeId: $storeId,
                );
            }

            if ($amountPaid > 0) {
                PaymentService::recordPayment(
                    type: 'payment_out',
                    amount: $amountPaid,
                    paymentMethod: $data['payment_method'] ?? 'cash',
                    documentType: 'purchase',
                    documentId: $purchase->id,
                    customerId: null,
                    supplierId: $supplierId,
                    reference: $refId,
                    userId: $userId,
                    storeId: $storeId,
                );
            }

            return $purchase;
        });
    }

    public function voidPurchase(Purchase $purchase, string $reason, int $userId): Purchase
    {
        if ($purchase->status === 'void') {
            throw ValidationException::withMessages(['purchase' => 'Purchase is already voided.']);
        }

        return DB::transaction(function () use ($purchase, $reason, $userId) {
            $movements = StockMovement::withoutGlobalScope('store')
                ->where('document_type', 'purchase')
                ->where('document_id', $purchase->id)
                ->where('type', StockMovement::TYPE_PURCHASE)
                ->orderByDesc('id')
                ->get();

            if ($movements->isNotEmpty()) {
                foreach ($movements as $movement) {
                    $product = Product::findOrFail($movement->product_id);
                    $quantity = (int) abs($movement->quantity_change);

                    // Movement rows now record the branch's before/after, but cost
                    // reversal is a product-level calculation, so derive the global
                    // quantity that existed before the purchase was received.
                    $globalQuantityBeforePurchase = max(0, (int) $product->current_quantity - $quantity);

                    $this->inventory->reverseInboundCost(
                        product: $product,
                        quantity: $quantity,
                        unitCost: (float) $movement->unit_cost,
                        prevQuantity: $globalQuantityBeforePurchase,
                        storeId: $purchase->store_id,
                    );

                    $this->inventory->outbound(
                        product: $product,
                        quantity: $quantity,
                        type: StockMovement::TYPE_ADJUSTMENT,
                        reference: 'VOID:'.$purchase->ref_id,
                        reason: 'Purchase voided: '.$reason,
                        documentType: 'purchase',
                        documentId: $purchase->id,
                        userId: $userId,
                        allowNegative: true,
                        storeId: $purchase->store_id,
                    );
                }
            } else {
                $purchase->load('items');

                foreach ($purchase->items as $item) {
                    $product = Product::findOrFail($item->product_id);

                    $this->inventory->outbound(
                        product: $product,
                        quantity: $item->quantity,
                        type: StockMovement::TYPE_ADJUSTMENT,
                        reference: 'VOID:'.$purchase->ref_id,
                        reason: 'Purchase voided: '.$reason,
                        documentType: 'purchase',
                        documentId: $purchase->id,
                        userId: $userId,
                        allowNegative: true,
                        storeId: $purchase->store_id,
                    );
                }
            }

            $purchase->update([
                'status' => 'void',
                'amount_paid' => 0,
                'balance' => $purchase->total,
                'void_reason' => $reason,
                'voided_by' => $userId,
                'voided_at' => now(),
            ]);

            // Reversal (never delete): mark any current payments as reversed.
            Payment::where('document_type', 'purchase')
                ->where('document_id', $purchase->id)
                ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUCCESS])
                ->update(['status' => Payment::STATUS_REVERSED]);

            return $purchase;
        });
    }

    /**
     * Record an additional payment from the business to a supplier against a purchase.
     */
    public function paySupplier(
        Purchase $purchase,
        float $amount,
        ?string $method,
        int $userId,
        ?string $remarks = null,
        ?string $paymentDate = null
    ): Payment {
        if ($purchase->status !== 'completed') {
            throw ValidationException::withMessages(['amount' => 'Payments cannot be recorded on a '.$purchase->status.' purchase.']);
        }

        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
        }

        $balance = round((float) $purchase->balance, 2);
        if ($amount > $balance) {
            throw ValidationException::withMessages([
                'amount' => "Payment cannot exceed outstanding balance of ₦{$balance}.",
            ]);
        }

        return DB::transaction(function () use ($purchase, $amount, $method, $userId, $remarks, $paymentDate) {
            $payment = PaymentService::recordPayment(
                type: 'payment_out',
                amount: $amount,
                paymentMethod: $method ?? 'cash',
                documentType: 'purchase',
                documentId: $purchase->id,
                customerId: null,
                supplierId: $purchase->supplier_id,
                reference: null,
                userId: $userId,
                remarks: $remarks,
                paymentDate: $paymentDate,
                storeId: $purchase->store_id,
            );

            $purchase->update([
                'amount_paid' => round((float) $purchase->amount_paid + $amount, 2),
                'balance' => round((float) $purchase->balance - $amount, 2),
            ]);

            return $payment;
        });
    }
}
