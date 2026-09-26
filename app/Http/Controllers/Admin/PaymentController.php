<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PaymentLinkMail;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\PaystackService;
use App\Services\PurchaseService;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(
        protected SaleService $saleService,
        protected PurchaseService $purchaseService,
        protected OrderService $orderService,
        protected PaystackService $paystack
    ) {}

    public function index(Request $request): Response
    {
        $payments = Payment::query()
            ->with(['customer:id,name,phone', 'supplier:id,name'])
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('ref_id', 'like', "%{$s}%")
                    ->orWhere('reference', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$s}%"))
                    ->orWhereHas('supplier', fn ($c) => $c->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->from, fn ($q, $d) => $q->whereDate('payment_date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('payment_date', '<=', $d))
            ->latest('payment_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $totals = Payment::query()
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->status && $request->status !== 'all', fn ($q, $s) => $q->where('status', $s))
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'payment_in' THEN amount ELSE 0 END), 0) as received")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'payment_out' THEN amount ELSE 0 END), 0) as paid_out")
            ->first();

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'type', 'status', 'from', 'to']),
            'totals' => [
                'received' => (float) ($totals->received ?? 0),
                'paid_out' => (float) ($totals->paid_out ?? 0),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'document_type' => ['required', 'in:sale,purchase,order'],
            'document_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $payment = match ($data['document_type']) {
                'sale' => $this->saleService->receivePayment(
                    Sale::findOrFail($data['document_id']),
                    (float) $data['amount'],
                    $data['payment_method'],
                    $request->user()->id,
                    remarks: $data['remarks'] ?? null,
                    paymentDate: $data['payment_date'] ?? null,
                ),
                'purchase' => $this->purchaseService->paySupplier(
                    Purchase::findOrFail($data['document_id']),
                    (float) $data['amount'],
                    $data['payment_method'],
                    $request->user()->id,
                    remarks: $data['remarks'] ?? null,
                    paymentDate: $data['payment_date'] ?? null,
                ),
                'order' => $this->orderService->receivePayment(
                    Order::findOrFail($data['document_id']),
                    (float) $data['amount'],
                    $data['payment_method'],
                    $request->user()->id,
                    remarks: $data['remarks'] ?? null,
                    paymentDate: $data['payment_date'] ?? null,
                ),
            };
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        AuditLogger::log('recorded', 'payment', $payment->id, "Recorded ₦{$payment->amount} payment {$payment->ref_id} against $data[document_type] #$data[document_id]");

        return back()->with('success', 'Payment recorded.');
    }

    /**
     * Generate a Paystack payment link for the outstanding balance of a sale.
     * Returns JSON because the frontend opens the link in a new tab.
     */
    public function paystack(Request $request): JsonResponse
    {
        $data = $request->validate([
            'document_type' => ['required', 'in:sale'],
            'document_id' => ['required', 'integer'],
        ]);

        if (! $this->paystack->isConfigured()) {
            return response()->json(['message' => 'Paystack is not configured on this server.'], 503);
        }

        $sale = Sale::with('customer')->findOrFail($data['document_id']);

        if ($sale->status !== 'completed') {
            return response()->json(['message' => 'This sale is no longer payable.'], 422);
        }

        $balance = round((float) $sale->balance, 2);
        if ($balance <= 0) {
            return response()->json(['message' => 'There is no outstanding balance on this invoice.'], 422);
        }

        $email = $sale->customer?->email
            ?? Setting::where('key', 'business.email')->value('value')
            ?? $request->user()->email;

        if (! $email) {
            return response()->json(['message' => 'A customer email is required to send a Paystack payment link.'], 422);
        }

        // Reuse an existing pending Paystack payment for this invoice to avoid duplicates.
        $payment = Payment::where('document_type', 'sale')
            ->where('document_id', $sale->id)
            ->where('gateway', Payment::GATEWAY_PAYSTACK)
            ->where('status', Payment::STATUS_PENDING)
            ->latest('id')
            ->first();

        $payment ??= PaymentService::recordPayment(
            type: 'payment_in',
            amount: $balance,
            paymentMethod: 'paystack',
            documentType: 'sale',
            documentId: $sale->id,
            customerId: $sale->customer_id,
            supplierId: null,
            reference: null,
            userId: $request->user()->id,
            gateway: Payment::GATEWAY_PAYSTACK,
            status: Payment::STATUS_PENDING,
        );

        try {
            $response = $this->paystack->initialize(
                reference: $payment->ref_id,
                amount: $balance,
                email: $email,
                callbackUrl: route('admin.sales.show', $sale),
                metadata: [
                    'payment_ref' => $payment->ref_id,
                    'invoice' => $sale->invoice_no,
                    'customer_name' => $sale->customer?->name,
                ],
            );
        } catch (\Throwable $e) {
            Log::error('Paystack initialize failed: '.$e->getMessage(), ['payment' => $payment->ref_id]);

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
     * Send Paystack payment link to customer via email.
     */
    public function sendPaymentLinkEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'document_type' => ['required', 'in:sale'],
            'document_id' => ['required', 'integer'],
            'custom_message' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $this->paystack->isConfigured()) {
            return response()->json(['message' => 'Paystack is not configured on this server.'], 503);
        }

        $sale = Sale::with('customer')->findOrFail($data['document_id']);

        if ($sale->status !== 'completed') {
            return response()->json(['message' => 'This sale is no longer payable.'], 422);
        }

        $balance = round((float) $sale->balance, 2);
        if ($balance <= 0) {
            return response()->json(['message' => 'There is no outstanding balance on this invoice.'], 422);
        }

        $email = $sale->customer?->email;
        if (! $email) {
            return response()->json(['message' => 'Customer does not have an email address.'], 422);
        }

        // Create or reuse a pending Paystack payment for this invoice
        $payment = Payment::where('document_type', 'sale')
            ->where('document_id', $sale->id)
            ->where('gateway', Payment::GATEWAY_PAYSTACK)
            ->where('status', Payment::STATUS_PENDING)
            ->latest('id')
            ->first();

        $payment ??= PaymentService::recordPayment(
            type: 'payment_in',
            amount: $balance,
            paymentMethod: 'paystack',
            documentType: 'sale',
            documentId: $sale->id,
            customerId: $sale->customer_id,
            supplierId: null,
            reference: null,
            userId: $request->user()->id,
            gateway: Payment::GATEWAY_PAYSTACK,
            status: Payment::STATUS_PENDING,
        );

        try {
            $response = $this->paystack->initialize(
                reference: $payment->ref_id,
                amount: $balance,
                email: $email,
                callbackUrl: route('admin.sales.show', $sale),
                metadata: [
                    'payment_ref' => $payment->ref_id,
                    'invoice' => $sale->invoice_no,
                    'customer_name' => $sale->customer?->name,
                ],
            );
        } catch (\Throwable $e) {
            Log::error('Paystack initialize failed: '.$e->getMessage(), ['payment' => $payment->ref_id]);

            return response()->json(['message' => 'Could not reach Paystack. Please try again.'], 502);
        }

        if (empty($response['status']) || empty($response['data']['authorization_url'] ?? null)) {
            Log::error('Paystack initialize returned an invalid response.', ['response' => $response]);

            return response()->json(['message' => 'Paystack did not return a payment link.'], 502);
        }

        $payment->update(['gateway_reference' => $payment->ref_id]);

        $paymentUrl = $response['data']['authorization_url'];

        // Send email to customer
        try {
            Mail::to($email, $sale->customer->name)->send(
                new PaymentLinkMail(
                    customer: $sale->customer,
                    sale: $sale,
                    paymentUrl: $paymentUrl,
                    customMessage: $data['custom_message'] ?? null,
                )
            );
        } catch (\Throwable $e) {
            Log::error('Payment link email failed to send', [
                'customer_email' => $email,
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Payment link generated but email failed to send.',
                'authorization_url' => $paymentUrl,
            ], 207);
        }

        return response()->json([
            'message' => 'Payment link sent to customer email.',
            'authorization_url' => $paymentUrl,
        ]);
    }
}
