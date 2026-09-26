<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates storefront orders and tracks their payments.
 *
 * An order's paid amount is derived from its successful `payment_in` records
 * (document_type = 'order') rather than a denormalised balance column, so the
 * same payments ledger that serves sales and purchases also serves orders.
 */
class OrderService
{
    public function __construct(protected InventoryService $inventory) {}

    /**
     * Create an order from storefront cart lines. Prices always come from the
     * database (selling_price), never from the client's cart payload.
     *
     * @param  array  $data  customer_name, customer_phone, customer_email, delivery_address
     * @param  array  $items  each ['product_id' => int, 'quantity' => int]
     */
    public function createOrder(array $data, array $items, ?int $userId = null): Order
    {
        foreach ($items as $item) {
            if (empty($item['product_id']) || empty($item['quantity']) || (int) $item['quantity'] < 1) {
                throw ValidationException::withMessages([
                    'items' => 'One of the cart lines is missing a product or quantity.',
                ]);
            }

            $product = Product::find($item['product_id']);

            if (! $product || ! $product->is_visible_online || ! $product->allow_online_purchase) {
                throw ValidationException::withMessages([
                    'items' => 'One of the selected products is no longer available for online purchase.',
                ]);
            }

            if ($product->current_quantity < (int) $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => "Insufficient stock for {$product->name}. Only {$product->current_quantity} unit(s) available.",
                ]);
            }
        }

        $order = DB::transaction(function () use ($data, $items, $userId) {
            $subtotal = 0;
            $prepared = [];

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = (int) $item['quantity'];
                $unitPrice = (float) $product->selling_price;
                $lineTotal = round($quantity * $unitPrice, 2);

                $prepared[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
                $subtotal += $lineTotal;
            }

            $subtotal = round($subtotal, 2);
            $taxRate = (float) (Setting::where('key', 'tax.rate')->value('value') ?? 0);
            $tax = round($subtotal * ($taxRate / 100), 2);
            $total = round($subtotal + $tax, 2);

            $order = Order::create([
                'ref_id' => ReferenceGenerator::generate('order'),
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => $tax,
                'delivery_fee' => 0,
                'total' => $total,
                'status' => 'pending',
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'delivery_address' => $data['delivery_address'],
                'fulfillment' => 'pending',
            ]);

            foreach ($prepared as $pi) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $pi['product_id'],
                    'quantity' => $pi['quantity'],
                    'unit_price' => $pi['unit_price'],
                    'total' => $pi['line_total'],
                ]);
            }

            return $order;
        });

        NotificationService::notifyAdmins(
            'New website order',
            "{$order->ref_id} — {$order->customer_name} placed an order worth ₦".number_format((float) $order->total, 2).'.',
            'order',
            route('admin.orders.show', $order->id),
            ['orders.view'],
        );

        return $order;
    }

    /**
     * Sum of successful payments recorded against an order.
     */
    public function totalPaid(Order $order): float
    {
        return round((float) $order->payments()
            ->where('status', Payment::STATUS_SUCCESS)
            ->sum('amount'), 2);
    }

    /**
     * Remaining amount owed on an order after successful payments.
     */
    public function balance(Order $order): float
    {
        return round((float) $order->total - $this->totalPaid($order), 2);
    }

    /**
     * Apply a successful payment to an order and flip its status to "paid" once
     * the full total is covered.
     *
     * @param  Payment|null  $payment  An existing pending payment (Paystack or
     *                                 offline bank transfer) to confirm.
     *
     * @throws ValidationException
     */
    public function receivePayment(
        Order $order,
        float $amount,
        ?string $method,
        ?int $userId,
        ?Payment $payment = null,
        ?string $remarks = null,
        ?string $paymentDate = null
    ): Payment {
        if (in_array($order->status, ['cancelled', 'refunded'])) {
            throw ValidationException::withMessages(['amount' => 'Payments cannot be recorded on a '.$order->status.' order.']);
        }

        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
        }

        $balance = $this->balance($order);
        if ($amount > $balance) {
            throw ValidationException::withMessages([
                'amount' => "Payment cannot exceed outstanding balance of ₦{$balance}.",
            ]);
        }

        return DB::transaction(function () use ($order, $amount, $method, $userId, $payment, $remarks, $paymentDate) {
            if ($payment !== null) {
                if ($payment->status !== Payment::STATUS_PENDING) {
                    throw ValidationException::withMessages(['amount' => 'This payment has already been processed.']);
                }

                $payment->update([
                    'status' => Payment::STATUS_SUCCESS,
                    'paid_at' => now(),
                ]);
            } else {
                $payment = PaymentService::recordPayment(
                    type: 'payment_in',
                    amount: $amount,
                    paymentMethod: $method ?? 'bank_transfer',
                    documentType: 'order',
                    documentId: $order->id,
                    customerId: $order->customer_id,
                    supplierId: null,
                    reference: null,
                    userId: $userId,
                    remarks: $remarks,
                    paymentDate: $paymentDate,
                );
            }

            if ($this->totalPaid($order) >= (float) $order->total) {
                $order->update([
                    'status' => 'paid',
                    'payment_reference' => $payment->ref_id,
                    'payment_provider' => $payment->gateway ?? $payment->payment_method,
                ]);
            }

            return $payment;
        });

        NotificationService::notifyAdmins(
            'Order payment received',
            '₦'.number_format((float) $amount, 2)." received for order {$order->ref_id}.",
            'payment',
            route('admin.orders.show', $order->id),
            ['orders.view'],
        );
    }

    /**
     * Fulfill an order by deducting stock for each item and recording stock movements.
     * Should be called when an order is marked as delivered/fulfilled.
     *
     * @throws ValidationException
     */
    public function fulfillOrder(Order $order, int $userId): Order
    {
        if ($order->fulfillment === 'delivered') {
            throw ValidationException::withMessages(['order' => 'Order is already fulfilled.']);
        }

        if (! in_array($order->status, ['paid', 'processing', 'completed'])) {
            throw ValidationException::withMessages(['order' => 'Order must be paid before it can be fulfilled.']);
        }

        $order->load('items.product');

        return DB::transaction(function () use ($order, $userId) {
            foreach ($order->items as $item) {
                $product = $item->product;

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => "Product for order item {$item->id} no longer exists.",
                    ]);
                }

                $this->inventory->outbound(
                    product: $product,
                    quantity: $item->quantity,
                    type: StockMovement::TYPE_SALE,
                    reference: 'ORDER:'.$order->ref_id,
                    documentType: 'order',
                    documentId: $order->id,
                    userId: $userId,
                );
            }

            $order->update([
                'fulfillment' => 'delivered',
                'status' => 'completed',
            ]);

            return $order;
        });
    }
}
