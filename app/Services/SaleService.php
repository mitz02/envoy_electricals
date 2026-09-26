<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(protected InventoryService $inventory) {}

    /**
     * Create a sale with its items. Everything (sale + inventory + ledger) is atomic.
     *
     * @param  array  $items  Each: ['product_id' => int, 'quantity' => int, 'unit_price' => float]
     */
    public function createSale(array $data, array $items, int $userId): Sale
    {
        $storeId = (int) ($data['store_id']
            ?? session('admin_store_id')
            ?? auth()->user()?->store_id
            ?? Store::where('is_default', true)->value('id')
            ?? Store::value('id')
            ?? 1);

        // Validate store-specific stock availability upfront before any mutation.
        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $available = $this->inventory->getStoreStock($product, $storeId);
            if ($available < $item['quantity']) {
                $storeName = Store::where('id', $storeId)->value('name') ?? "Store #{$storeId}";
                throw ValidationException::withMessages([
                    'items' => "Insufficient stock for {$product->name} at {$storeName}. Available: {$available}, Requested: {$item['quantity']}.",
                ]);
            }
        }

        return DB::transaction(function () use ($data, $items, $userId, $storeId) {
            $subtotal = 0;
            $preparedItems = [];

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $lineTotal = round((float) $item['unit_price'] * (int) $item['quantity'], 2);
                $preparedItems[] = [
                    'product' => $product,
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (float) $item['unit_price'],
                    'line_total' => $lineTotal,
                ];
                $subtotal += $lineTotal;
            }

            $subtotal = round($subtotal, 2);
            $discount = round((float) ($data['discount'] ?? 0), 2);
            $taxRate = (float) ($data['tax_rate'] ?? 0);
            $taxableBase = max($subtotal - $discount, 0);
            $tax = round($taxableBase * ($taxRate / 100), 2);
            $total = round($taxableBase + $tax, 2);

            // Ensure a customer exists.
            $customerId = $this->resolveCustomer($data, $userId, $storeId);

            // Check if customer is walk-in
            $isWalkIn = false;
            if ($customerId !== null) {
                $customer = Customer::find($customerId);
                $isWalkIn = $customer && $customer->customer_type === 'walk_in';
            }

            $amountPaid = round((float) ($data['amount_paid'] ?? 0), 2);

            // Walk-in customers must pay in full
            if ($isWalkIn) {
                $amountPaid = $total;
            }

            // Any excess beyond the invoice total is applied toward the customer's
            // previous outstanding balance (oldest invoices first).
            $outstanding = 0.0;
            if ($customerId !== null) {
                $outstanding = round((float) Sale::where('customer_id', $customerId)
                    ->where('status', 'completed')
                    ->where('balance', '>', 0)
                    ->sum('balance'), 2);
            }

            $maxPayable = round($total + $outstanding, 2);
            if ($amountPaid > $maxPayable) {
                throw ValidationException::withMessages([
                    'amount_paid' => $outstanding > 0
                        ? 'Payment cannot exceed the invoice total plus the customer\'s outstanding balance of ₦'.number_format($outstanding, 2, '.', ',').'.'
                        : 'Payment cannot exceed the invoice total.',
                ]);
            }

            $amountPaidBase = round(min($amountPaid, $total), 2);
            $excess = round($amountPaid - $amountPaidBase, 2);
            $balance = round($total - $amountPaidBase, 2);

            $invoiceNo = ReferenceGenerator::generate('invoice');

            $sale = Sale::create([
                'ref_id' => ReferenceGenerator::generate('sale'),
                'invoice_no' => $invoiceNo,
                'sale_date' => $data['sale_date'] ?? now()->toDateString(),
                'store_id' => $storeId,
                'customer_id' => $customerId,
                'salesperson_id' => $userId,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax_rate' => $taxRate,
                'tax' => $tax,
                'total' => $total,
                'amount_paid' => $amountPaidBase,
                'balance' => $balance,
                'payment_method' => $data['payment_method'] ?? null,
                'status' => 'completed',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
            ]);

            $totalCost = 0;

            foreach ($preparedItems as $pi) {
                $product = $pi['product'];
                $unitCost = (float) $product->average_cost;
                $lineCost = round($unitCost * $pi['quantity'], 2);
                $totalCost += $lineCost;
                $profit = round($pi['line_total'] - $lineCost, 2);

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => $pi['quantity'],
                    'unit_price' => $pi['unit_price'],
                    'unit_cost' => $unitCost,
                    'total' => $pi['line_total'],
                    'profit' => $profit,
                ]);

                // Deduct stock atomically from the specific store.
                $this->inventory->outbound(
                    product: $product,
                    quantity: $pi['quantity'],
                    type: StockMovement::TYPE_SALE,
                    reference: $invoiceNo,
                    documentType: 'sale',
                    documentId: $sale->id,
                    userId: $userId,
                    storeId: $storeId,
                );
            }

            // Record the payment received (if any) as a payment_in record.
            if ($amountPaidBase > 0) {
                PaymentService::recordPayment(
                    type: 'payment_in',
                    amount: $amountPaidBase,
                    paymentMethod: $data['payment_method'] ?? 'cash',
                    documentType: 'sale',
                    documentId: $sale->id,
                    customerId: $customerId,
                    supplierId: null,
                    reference: $invoiceNo,
                    userId: $userId,
                    storeId: $storeId,
                );
            }

            // Apply any excess payment toward the customer's previous invoices (oldest first).
            if ($excess > 0 && $customerId !== null) {
                $remaining = $excess;
                $outstandingSales = Sale::where('customer_id', $customerId)
                    ->where('status', 'completed')
                    ->where('balance', '>', 0)
                    ->orderBy('sale_date')
                    ->orderBy('id')
                    ->get(['id', 'customer_id', 'status', 'balance']);

                foreach ($outstandingSales as $invoice) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $apply = round(min($remaining, (float) $invoice->balance), 2);
                    if ($apply <= 0) {
                        continue;
                    }

                    $this->receivePayment(
                        $invoice,
                        $apply,
                        $data['payment_method'] ?? 'cash',
                        $userId,
                        remarks: 'Applied from excess payment on sale '.$invoiceNo,
                        paymentDate: $data['sale_date'] ?? now()->toDateString(),
                    );

                    $remaining = round($remaining - $apply, 2);
                }
            }

            $sale->excess_applied = $excess;
            $sale->previous_outstanding = $outstanding;

            return $sale;
        });
    }

    protected function resolveCustomer(array $data, int $userId, int $storeId = 1): ?int
    {
        if (! empty($data['customer_id'])) {
            return (int) $data['customer_id'];
        }

        if (! empty($data['customer_name'])) {
            $customer = Customer::create([
                'ref_id' => ReferenceGenerator::generate('customer'),
                'name' => $data['customer_name'],
                'phone' => $data['customer_phone'] ?? null,
                'customer_type' => 'walk_in',
                'store_id' => $storeId,
            ]);

            return $customer->id;
        }

        return null;
    }

    /**
     * Void (not delete) a sale and reverse all affected inventory.
     */
    public function voidSale(Sale $sale, string $reason, int $userId): Sale
    {
        if ($sale->status === 'void') {
            throw ValidationException::withMessages(['sale' => 'Sale is already voided.']);
        }

        return DB::transaction(function () use ($sale, $reason, $userId) {
            $sale->load('items');

            foreach ($sale->items as $item) {
                $product = Product::findOrFail($item->product_id);
                $this->inventory->inbound(
                    product: $product,
                    quantity: $item->quantity,
                    type: StockMovement::TYPE_RETURN,
                    reference: 'VOID:'.$sale->invoice_no,
                    reason: 'Sale voided: '.$reason,
                    documentType: 'sale',
                    documentId: $sale->id,
                    userId: $userId,
                    storeId: $sale->store_id,
                );
            }

            $sale->update([
                'status' => 'void',
                'amount_paid' => 0,
                'balance' => $sale->total,
                'void_reason' => $reason,
                'voided_by' => $userId,
                'voided_at' => now(),
            ]);

            // Reversal (never delete): mark any collected payments as reversed.
            Payment::where('document_type', 'sale')
                ->where('document_id', $sale->id)
                ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUCCESS])
                ->update(['status' => Payment::STATUS_REVERSED]);

            return $sale;
        });
    }

    /**
     * Record an additional payment against a sale and update the outstanding balance.
     */
    public function receivePayment(
        Sale $sale,
        float $amount,
        ?string $method,
        int $userId,
        ?Payment $payment = null,
        ?string $remarks = null,
        ?string $paymentDate = null,
    ): Payment {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
        }

        $balance = (float) $sale->balance;
        if ($amount > $balance) {
            throw ValidationException::withMessages([
                'amount' => 'Payment exceeds outstanding balance of ₦'.number_format($balance, 2, '.', ',').'.',
            ]);
        }

        return DB::transaction(function () use ($sale, $amount, $method, $userId, $payment, $remarks, $paymentDate) {
            $newPaid = round((float) $sale->amount_paid + $amount, 2);
            $newBalance = max(round((float) $sale->total - $newPaid, 2), 0);

            $sale->update([
                'amount_paid' => $newPaid,
                'balance' => $newBalance,
            ]);

            if ($payment !== null) {
                $payment->update([
                    'status' => Payment::STATUS_SUCCESS,
                    'paid_at' => now(),
                    'remarks' => $remarks ?? $payment->remarks,
                ]);

                return $payment;
            }

            return PaymentService::recordPayment(
                type: 'payment_in',
                amount: $amount,
                paymentMethod: $method ?? 'cash',
                documentType: 'sale',
                documentId: $sale->id,
                customerId: $sale->customer_id,
                supplierId: null,
                reference: $sale->invoice_no,
                userId: $userId,
                remarks: $remarks,
                paymentDate: $paymentDate,
                storeId: $sale->store_id,
            );
        });
    }
}
