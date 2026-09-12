<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\PaystackService;
use App\Services\ReferenceGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Public storefront checkout: create an order from the client-side cart and
 * accept payment via Paystack or via an offline bank transfer.
 */
class CheckoutController extends Controller
{
    public function __construct(
        protected OrderService $orders,
        protected PaystackService $paystack
    ) {}

    public function cart(): \Inertia\Response
    {
        return Inertia::render('Storefront/Cart');
    }

    public function checkout(): \Inertia\Response
    {
        return Inertia::render('Storefront/Checkout');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ], [
            'items.required' => 'Your cart is empty. Add a product before checking out.',
        ]);

        try {
            $order = $this->orders->createOrder(
                data: $data,
                items: $data['items'],
                userId: $request->user()?->id,
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('orders.pay', $order->ref_id)
            ->with('success', "Order {$order->ref_id} placed! Complete payment to confirm your order.");
    }

    public function pay(Order $order): \Inertia\Response
    {
        $order->load(['items.product.images']);

        return Inertia::render('Storefront/OrderPay', [
            'order' => $order,
            'payments' => $order->payments()->latest('id')->get(),
            'paid' => $this->orders->totalPaid($order),
            'balance' => $this->orders->balance($order),
            'paystackConfigured' => $this->paystack->isConfigured(),
            'businessEmail' => Setting::where('key', 'business.email')->value('value'),
            'bank' => Setting::where('group', 'bank')->pluck('value', 'key'),
        ]);
    }

    /**
     * Initialize a Paystack transaction for the order's outstanding balance.
     * Returns JSON because the frontend opens the authorization URL directly.
     */
    public function paystack(Request $request, Order $order): JsonResponse
    {
        if (! $this->paystack->isConfigured()) {
            return response()->json(['message' => 'Paystack is not configured on this server.'], 503);
        }

        if (in_array($order->status, ['cancelled', 'refunded']) || $order->status === 'paid') {
            return response()->json(['message' => 'This order is no longer payable.'], 422);
        }

        $balance = round($this->orders->balance($order), 2);
        if ($balance <= 0) {
            return response()->json(['message' => 'There is no outstanding balance on this order.'], 422);
        }

        $email = $order->customer_email
            ?? Setting::where('key', 'business.email')->value('value');

        if (! $email) {
            return response()->json(['message' => 'A valid email is required to pay with Paystack.'], 422);
        }

        // Reuse an existing pending Paystack payment for this order to avoid duplicates.
        $payment = Payment::where('document_type', 'order')
            ->where('document_id', $order->id)
            ->where('gateway', Payment::GATEWAY_PAYSTACK)
            ->where('status', Payment::STATUS_PENDING)
            ->latest('id')
            ->first();

        $payment ??= PaymentService::recordPayment(
            type: 'payment_in',
            amount: $balance,
            paymentMethod: 'paystack',
            documentType: 'order',
            documentId: $order->id,
            customerId: null,
            supplierId: null,
            reference: null,
            userId: $request->user()?->id,
            gateway: Payment::GATEWAY_PAYSTACK,
            status: Payment::STATUS_PENDING,
        );

        // An already-initialized reference cannot be re-initialized with Paystack
        // (duplicate_reference). Give this attempt a fresh reference so retries work.
        if ($payment->gateway_reference) {
            $payment->forceFill(['ref_id' => ReferenceGenerator::generate('payment')])->save();
        }

        try {
            $response = $this->paystack->initialize(
                reference: $payment->ref_id,
                amount: $balance,
                email: $email,
                callbackUrl: route('orders.pay', $order->ref_id),
                metadata: [
                    'payment_ref' => $payment->ref_id,
                    'order' => $order->ref_id,
                    'customer_name' => $order->customer_name,
                ],
            );
        } catch (\Throwable $e) {
            $detail = $e->getMessage();
            if (method_exists($e, 'response') && $e->response) {
                $json = $e->response->json();
                $detail .= ' :: ' . ($json['message'] ?? $e->response->body());
            }

            Log::error('Paystack initialize failed: ' . $detail, ['payment' => $payment->ref_id]);

            return response()->json(['message' => 'Could not reach Paystack. Please try again.'], 502);
        }

        if (empty($response['status']) || empty($response['data']['authorization_url'] ?? null)) {
            Log::error('Paystack initialize returned an invalid response.', ['response' => $response]);

            return response()->json(['message' => 'Paystack did not return a payment link.'], 502);
        }

        $payment->update(['gateway_reference' => $payment->ref_id]);

        return response()->json([
            'authorization_url' => $response['data']['authorization_url'],
            'reference' => $payment->ref_id,
        ]);
    }

    /**
     * Confirm an offline bank transfer intent. The order's payment stays pending
     * until an admin verifies the transfer and confirms it.
     */
    public function offline(Request $request, Order $order): RedirectResponse
    {
        if (in_array($order->status, ['cancelled', 'refunded']) || $this->orders->balance($order) <= 0) {
            return back()->with('error', 'This order is no longer payable.');
        }

        $this->orders->requestOffline($order, $request->user()?->id);

        return back()->with('success', "Transfer confirmed! We'll verify your payment and update order {$order->ref_id} shortly.");
    }
}