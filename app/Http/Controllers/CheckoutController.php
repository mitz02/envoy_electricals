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
use Inertia\Response;

/**
 * Public storefront checkout: create an order from the client-side cart and
 * accept payment via Paystack.
 */
class CheckoutController extends Controller
{
    public function __construct(
        protected OrderService $orders,
        protected PaystackService $paystack
    ) {}

    public function cart(): Response
    {
        return Inertia::render('Storefront/Cart');
    }

    public function checkout(): Response
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

    public function pay(Order $order): Response
    {
        $order->load(['items.product.images']);

        return Inertia::render('Storefront/OrderPay', [
            'order' => $order,
            'payments' => $order->payments()->latest('id')->get(),
            'paid' => $this->orders->totalPaid($order),
            'balance' => $this->orders->balance($order),
            'paystackConfigured' => $this->paystack->isConfigured(),
            'businessEmail' => Setting::where('key', 'business.email')->value('value'),
            'whatsappNumber' => preg_replace('/\D/', '', (string) Setting::where('key', 'business.phone')->value('value')),
            'bankAccountName' => Setting::where('key', 'bank.account_name')->value('value'),
            'bankAccountNumber' => Setting::where('key', 'bank.account_number')->value('value'),
            'bankName' => Setting::where('key', 'bank.bank_name')->value('value'),
            'bankInstructions' => Setting::where('key', 'bank.instructions')->value('value'),
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

        // Generate a fresh reference for THIS Paystack attempt.
        // We do NOT save it to the payment yet - only after Paystack succeeds.
        // This avoids "Duplicate Transaction Reference" if the call fails and user retries.
        // Add timestamp + random to avoid collisions with previous test runs on Paystack test mode.
        $paystackReference = ReferenceGenerator::generate('payment').'-'.bin2hex(random_bytes(4));

        try {
            $response = $this->paystack->initialize(
                reference: $paystackReference,
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
                $detail .= ' :: '.($json['message'] ?? $e->response->body());
            }

            Log::error('Paystack initialize failed: '.$detail, ['payment' => $payment->ref_id]);

            return response()->json(['message' => 'Could not reach Paystack. Please try again.'], 502);
        }

        if (empty($response['status']) || empty($response['data']['authorization_url'] ?? null)) {
            Log::error('Paystack initialize returned an invalid response.', ['response' => $response]);

            return response()->json(['message' => 'Paystack did not return a payment link.'], 502);
        }

        // Only now update the payment with the successful Paystack reference
        $payment->update([
            'gateway_reference' => $paystackReference,
            'ref_id' => $paystackReference,
        ]);

        return response()->json([
            'authorization_url' => $response['data']['authorization_url'],
            'reference' => $paystackReference,
        ]);
    }

    /**
     * Verify a Paystack payment from the callback URL.
     * Called by the frontend when user returns from Paystack with reference/trxref params.
     */
    public function verify(Request $request, Order $order): JsonResponse
    {
        $reference = $request->query('reference') ?? $request->query('trxref');

        if (! $reference) {
            return response()->json(['success' => false, 'message' => 'No payment reference provided.'], 400);
        }

        if (! $this->paystack->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'Paystack is not configured.'], 503);
        }

        try {
            $verification = $this->paystack->verify($reference);
        } catch (\Throwable $e) {
            Log::error('Paystack verify failed: '.$e->getMessage(), ['reference' => $reference]);

            return response()->json(['success' => false, 'message' => 'Could not verify payment.'], 502);
        }

        $statusOk = $this->paystack->isSuccessfulVerification($verification);

        if (! $statusOk) {
            return response()->json(['success' => false, 'message' => 'Payment not successful.']);
        }

        // Find and mark the payment as successful
        $payment = Payment::where('document_type', 'order')
            ->where('document_id', $order->id)
            ->where('gateway', Payment::GATEWAY_PAYSTACK)
            ->where(function ($q) use ($reference) {
                $q->where('gateway_reference', $reference)->orWhere('ref_id', $reference);
            })
            ->first();

        if ($payment && $payment->status !== Payment::STATUS_SUCCESS) {
            $amountOk = ((int) ($verification['data']['amount'] ?? 0)) === (int) round((float) $payment->amount * 100);
            if ($amountOk) {
                $this->orderService->receivePayment(
                    order: $order,
                    amount: (float) $payment->amount,
                    method: 'paystack',
                    userId: $payment->created_by,
                    payment: $payment,
                );
            }
        }

        return response()->json(['success' => true, 'message' => 'Payment verified successfully.']);
    }
}
