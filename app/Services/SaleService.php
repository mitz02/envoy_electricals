<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(protected InventoryService $inventory) {}

    /**
     * Create a sale with its items. Everything (sale + inventory + ledger) is atomic.
     *
     * @param array $items Each: ['product_id' => int, 'quantity' => int, 'unit_price' => float]
     */
    public function createSale(array $data, array $items, int $userId): Sale
    {
        // Validate stock availability upfront before any mutation.
        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);
            if ($product->current_quantity < $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => "Insufficient stock for {$product->name}. Only {$product->current_quantity} unit(s) available.",
                ]);
            }
        }

        return DB::transaction(function () use ($data, $items, $userId) {
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

            $amountPaid = round((float) ($data['amount_paid'] ?? 0), 2);
            if ($amountPaid > $total) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Payment cannot exceed invoice total.',
                ]);
            }
            $balance = round($total - $amountPaid, 2);

            // Ensure a customer exists.
            $customerId = $this->resolveCustomer($data, $userId);

            $invoiceNo = ReferenceGenerator::generate('invoice');

            $sale = Sale::create([
                'ref_id' => ReferenceGenerator::generate('sale'),
                'invoice_no' => $invoiceNo,
                'sale_date' => $data['sale_date'] ?? now()->toDateString(),
                'customer_id' => $customerId,
                'salesperson_id' => $userId,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax_rate' => $taxRate,
                'tax' => $tax,
                'total' => $total,
                'amount_paid' => $amountPaid,
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

                // Deduct stock atomically.
                $this->inventory->outbound(
                    product: $product,
                    quantity: $pi['quantity'],
                    type: StockMovement::TYPE_SALE,
                    reference: $invoiceNo,
                    documentType: 'sale',
                    documentId: $sale->id,
                    userId: $userId,
                );
            }

            // Record the payment received (if any) as a payment_in record.
            if ($amountPaid > 0) {
                PaymentService::recordPayment(
                    type: 'payment_in',
                    amount: $amountPaid,
                    paymentMethod: $data['payment_method'] ?? 'cash',
                    documentType: 'sale',
                    documentId: $sale->id,
                    customerId: $customerId,
                    supplierId: null,
                    reference: $invoiceNo,
                    userId: $userId,
                );
            }

            return $sale;
        });
    }

    protected function resolveCustomer(array $data, int $userId): ?int
    {
        if (!empty($data['customer_id'])) {
            return (int) $data['customer_id'];
        }

        if (!empty($data['customer_name'])) {
            $customer = Customer::create([
                'ref_id' => ReferenceGenerator::generate('customer'),
                'name' => $data['customer_name'],
                'phone' => $data['customer_phone'] ?? null,
                'customer_type' => 'walk_in',
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
                    reference: 'VOID:' . $sale->invoice_no,
                    reason: 'Sale voided: ' . $reason,
                    documentType: 'sale',
                    documentId: $sale->id,
                    userId: $userId,
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
     *
     * @param  Payment|null  $payment  An existing pending Paystack payment to mark as successful.
     */
    public function receivePayment(
        Sale $sale,
        float $amount,
        ?string $method,
        int $userId,
        ?Payment $payment = null,
        ?string $remarks = null,
        ?string $paymentDate = null
    ): Payment {
        if ($sale->status !== 'completed') {
            throw ValidationException::withMessages(['amount' => 'Payments cannot be recorded on a ' . $sale->status . ' sale.']);
        }

        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
        }

        $balance = round((float) $sale->balance, 2);
        if ($amount > $balance) {
            throw ValidationException::withMessages([
                'amount' => "Payment cannot exceed outstanding balance of ₦{$balance}.",
            ]);
        }

        return DB::transaction(function () use ($sale, $amount, $method, $userId, $payment, $remarks, $paymentDate) {
            if ($payment !== null) {
                if ($payment->status !== Payment::STATUS_PENDING) {
                    throw ValidationException::withMessages(['amount' => 'This payment has already been processed.']);
                }

                $payment->update([
                    'status' => Payment::STATUS_SUCCESS,
                    'gateway' => Payment::GATEWAY_PAYSTACK,
                    'payment_method' => 'paystack',
                    'paid_at' => now(),
                ]);
            } else {
                $payment = PaymentService::recordPayment(
                    type: 'payment_in',
                    amount: $amount,
                    paymentMethod: $method ?? 'cash',
                    documentType: 'sale',
                    documentId: $sale->id,
                    customerId: $sale->customer_id,
                    supplierId: null,
                    reference: null,
                    userId: $userId,
                    remarks: $remarks,
                    paymentDate: $paymentDate,
                );
            }

            $sale->update([
                'amount_paid' => round((float) $sale->amount_paid + $amount, 2),
                'balance' => round((float) $sale->balance - $amount, 2),
            ]);

            return $payment;
        });
    }
}
