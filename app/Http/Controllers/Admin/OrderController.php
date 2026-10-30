<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\AuditLogger;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    public function index(Request $request): Response
    {
        $orders = Order::query()
            ->withCount('items')
            ->withSum(['payments as paid_sum' => fn ($q) => $q->where('status', Payment::STATUS_SUCCESS)], 'amount')
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('ref_id', 'like', "%{$s}%")
                    ->orWhere('customer_name', 'like', "%{$s}%")
                    ->orWhere('customer_phone', 'like', "%{$s}%");
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->delivery, fn ($q, $d) => $q->where('fulfillment', $d))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $totals = Order::query()
            ->selectRaw('count(*) as total_orders')
            ->selectRaw("COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total ELSE 0 END), 0) as booking_value")
            // Sum real successful payments rather than inferring from status:
            // a delivered order becomes 'completed', and assuming full payment
            // over-reports part-paid orders. Cancelled/refunded orders are
            // excluded because that money went back out.
            ->selectSub(
                Payment::query()
                    ->selectRaw('COALESCE(SUM(payments.amount), 0)')
                    ->join('orders', 'orders.id', '=', 'payments.document_id')
                    ->where('payments.document_type', '=', 'order')
                    ->where('payments.status', '=', Payment::STATUS_SUCCESS)
                    ->whereNotIn('orders.status', ['cancelled', 'refunded'])
                    ->whereNull('orders.deleted_at'),
                'collected'
            )
            ->first();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['search', 'status', 'delivery']),
            'totals' => [
                'total_orders' => (int) ($totals->total_orders ?? 0),
                'booking_value' => (float) ($totals->booking_value ?? 0),
                'collected' => (float) ($totals->collected ?? 0),
            ],
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load(['items.product:id,name,sku', 'payments' => fn ($q) => $q->latest('id')]);

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order,
            'paid' => $this->orders->totalPaid($order),
            'balance' => $this->orders->balance($order),
        ]);
    }

    /**
     * Confirm an offline bank-transfer payment logged by a customer ("I have
     * transferred"). The pending payment is flipped to successful.
     */
    public function confirmPayment(Request $request, Order $order, Payment $payment): RedirectResponse
    {
        if ($payment->document_type !== 'order' || $payment->document_id !== $order->id) {
            return back()->with('error', 'This payment does not belong to the order.');
        }

        try {
            $payment = $this->orders->receivePayment(
                order: $order,
                amount: (float) $payment->amount,
                method: $payment->payment_method,
                userId: $request->user()->id,
                payment: $payment,
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        AuditLogger::log('recorded', 'payment', $payment->id, "Confirmed offline bank-transfer ₦{$payment->amount} for order {$order->ref_id}.");

        return back()->with('success', "Payment confirmed. Order {$order->ref_id} marked as paid.");
    }

    public function deliver(Request $request, Order $order): RedirectResponse
    {
        try {
            $order = $this->orders->fulfillOrder($order, $request->user()->id);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        AuditLogger::log('updated', 'order', $order->id, "Order {$order->ref_id} fulfilled and stock deducted.");

        return back()->with('success', "Order {$order->ref_id} fulfilled and stock updated.");
    }

    /**
     * Cancel an order. If paid, optionally issue a refund.
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'refund_reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $order = $this->orders->cancelOrder(
                $order,
                $request->user()->id,
                $request->input('refund_amount'),
                $request->input('refund_reason')
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $message = $order->status === 'refunded'
            ? "Order {$order->ref_id} cancelled and refunded."
            : "Order {$order->ref_id} cancelled.";

        AuditLogger::log('updated', 'order', $order->id, "Order {$order->ref_id} cancelled (status: {$order->status}).");

        return back()->with('success', $message);
    }

    /**
     * Mark an order as processing.
     */
    public function process(Request $request, Order $order): RedirectResponse
    {
        try {
            $order = $this->orders->markProcessing($order);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        AuditLogger::log('updated', 'order', $order->id, "Order {$order->ref_id} marked as processing.");

        return back()->with('success', "Order {$order->ref_id} is now being processed.");
    }

    /**
     * Record a manual payment for an order.
     */
    public function addPayment(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'in:cash,bank_transfer,card,pos,other'],
            'payment_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $payment = $this->orders->addPayment(
                $order,
                (float) $request->input('amount'),
                $request->input('payment_method'),
                $request->user()->id,
                $request->input('remarks'),
                $request->input('payment_date')
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        AuditLogger::log('recorded', 'payment', $payment->id, "Recorded manual payment ₦{$payment->amount} for order {$order->ref_id}.");

        return back()->with('success', 'Payment of ₦'.number_format((float) $payment->amount, 2)." recorded for order {$order->ref_id}.");
    }

    /**
     * Undo order fulfillment (restore stock).
     */
    public function undoDeliver(Request $request, Order $order): RedirectResponse
    {
        try {
            $order = $this->orders->undoFulfillment($order, $request->user()->id);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        AuditLogger::log('updated', 'order', $order->id, "Order {$order->ref_id} fulfillment undone, stock restored.");

        return back()->with('success', "Order {$order->ref_id} fulfillment undone. Stock restored.");
    }
}
