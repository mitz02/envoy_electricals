<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SolarPackage;
use App\Models\Training;
use App\Services\AcademyService;
use App\Services\AuditLogger;
use App\Services\OrderService;
use App\Services\PaystackService;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Paystack webhook handler.
 *
 * Security:
 *  - Request signature is verified with the Paystack webhook/HMAC secret.
 *  - Payment is only marked successful after a server-side call to the Paystack
 *    verify endpoint — never from the customer's browser response.
 *  - Handling is idempotent: a `charge.success` event that was already applied
 *    is a no-op, so retried webhooks never double-apply a payment (TEST 16).
 */
class PaystackWebhookController extends Controller
{
    public function __construct(
        protected PaystackService $paystack,
        protected SaleService $saleService,
        protected OrderService $orderService,
        protected AcademyService $academyService
    ) {}

    public function handle(Request $request): JsonResponse
    {
        if (! $this->paystack->signatureIsValid($request)) {
            Log::warning('Paystack webhook rejected (invalid signature).');

            return response()->json(['status' => 'rejected'], 400);
        }

        match ($request->input('event')) {
            'charge.success' => $this->handleChargeSuccess($request->input('data', [])),
            'transfer.success' => $this->handleTransferSuccess($request->input('data', [])),
            default => null,
        };

        return response()->json(['status' => 'success']);
    }

    protected function handleChargeSuccess(array $payload): void
    {
        $reference = (string) ($payload['reference'] ?? '');

        if ($reference === '') {
            return;
        }

        $payment = Payment::where('gateway', Payment::GATEWAY_PAYSTACK)
            ->where(function ($q) use ($reference) {
                $q->where('gateway_reference', $reference)->orWhere('reference', $reference);
            })
            ->first();

        if (! $payment) {
            Log::info('Paystack webhook for unknown payment reference.', ['reference' => $reference]);

            return;
        }

        // Idempotency: a payment already marked successful is never applied twice.
        if ($payment->status === Payment::STATUS_SUCCESS) {
            return;
        }

        try {
            $verification = $this->paystack->verify($reference);
        } catch (\Throwable $e) {
            Log::error('Paystack verification request failed.', ['reference' => $reference, 'error' => $e->getMessage()]);

            return;
        }

        $statusOk = $this->paystack->isSuccessfulVerification($verification);
        $amountOk = ((int) ($payload['amount'] ?? 0)) === (int) round((float) $payment->amount * 100);

        if (! $statusOk || ! $amountOk) {
            Log::warning('Paystack webhook: verification did not confirm charge success.', [
                'reference' => $reference,
                'payment' => $payment->ref_id,
                'amount_mismatch' => ! $amountOk,
            ]);
            $payment->update(['status' => Payment::STATUS_FAILED]);

            return;
        }

        DB::transaction(function () use ($payment) {
            match ($payment->document_type) {
                'sale' => $this->applyToSale($payment),
                'purchase' => $this->applyToPurchase($payment),
                'order' => $this->applyToOrder($payment),
                'enrollment' => $this->applyToEnrollment($payment),
                'solar_package' => $this->applyToPackage($payment),
                default => null,
            };

            // Anything left pending (e.g. an unsupported document type) was
            // still verified server-side, so record it as successful.
            if ($payment->status === Payment::STATUS_PENDING) {
                $payment->update([
                    'status' => Payment::STATUS_SUCCESS,
                    'paid_at' => now(),
                ]);
            }
        });

        AuditLogger::log('recorded', 'payment', $payment->id, "Paystack payment {$payment->ref_id} of ₦{$payment->amount} verified and applied.");
    }

    protected function handleTransferSuccess(array $payload): void
    {
        // Paystack transfers (payouts to suppliers/staff) are recorded when the
        // transfer is initiated; the event only confirms delivery so there is
        // nothing new to apply here.
        Log::info('Paystack transfer.success received.', ['reference' => $payload['reference'] ?? null]);
    }

    protected function applyToSale(Payment $payment): void
    {
        $sale = Sale::find($payment->document_id);

        if (! $sale || $sale->status !== 'completed') {
            Log::warning('Paystack payment could not be applied to sale.', ['payment' => $payment->ref_id]);
            $payment->update(['status' => Payment::STATUS_FAILED]);

            return;
        }

        // Mark the pending Paystack payment successful and apply it to the balance.
        $this->saleService->receivePayment(
            sale: $sale,
            amount: (float) $payment->amount,
            method: 'paystack',
            userId: (int) ($payment->created_by ?? 0),
            payment: $payment,
        );
    }

    protected function applyToPurchase(Payment $payment): void
    {
        // A charge.success never applies to a purchase (money we owe a supplier
        // is paid out via Paystack transfers, which fire transfer.success).
        Log::warning('Paystack charge.success referenced a purchase; nothing to apply.', ['payment' => $payment->ref_id]);
    }

    protected function applyToOrder(Payment $payment): void
    {
        $order = Order::find($payment->document_id);

        if (! $order || in_array($order->status, ['cancelled', 'refunded'])) {
            Log::warning('Paystack payment could not be applied to order.', ['payment' => $payment->ref_id]);
            $payment->update(['status' => Payment::STATUS_FAILED]);

            return;
        }

        $this->orderService->receivePayment(
            order: $order,
            amount: (float) $payment->amount,
            method: 'paystack',
            userId: $payment->created_by,
            payment: $payment,
        );
    }

    protected function applyToEnrollment(Payment $payment): void
    {
        $training = Training::find($payment->document_id);
        $trainee = $payment->trainee_id
            ? \App\Models\Trainee::find($payment->trainee_id)
            : ($payment->customer_id ? \App\Models\Trainee::find($payment->customer_id) : null);

        if (! $training || ! $trainee) {
            Log::warning('Paystack payment could not be applied to enrollment - missing training or trainee.', [
                'payment' => $payment->ref_id,
                'training_id' => $payment->document_id,
                'trainee_id' => $payment->trainee_id,
                'customer_id' => $payment->customer_id,
            ]);
            $payment->update(['status' => Payment::STATUS_FAILED]);

            return;
        }

        if ($training->is_active === false) {
            Log::warning('Paystack payment for inactive training program.', ['payment' => $payment->ref_id]);
            $payment->update(['status' => Payment::STATUS_FAILED]);

            return;
        }

        $capacity = $training->capacity;
        if ($capacity !== null && $training->enrolled_count >= (int) $capacity) {
            Log::warning('Paystack payment for full training program.', ['payment' => $payment->ref_id]);
            $payment->update(['status' => Payment::STATUS_FAILED]);

            return;
        }

        // Check if already enrolled
        $existing = Enrollment::where('trainee_id', $trainee->id)
            ->where('training_id', $training->id)
            ->exists();

        if ($existing) {
            Log::info('Trainee already enrolled, payment marked success but no new enrollment.', ['payment' => $payment->ref_id]);
            return;
        }

        try {
            $this->academyService->enroll($trainee, $training->id, $payment->created_by);
            AuditLogger::log('created', 'enrollment', 0, "Auto-enrolled {$trainee->name} in {$training->title} via Paystack payment {$payment->ref_id}", userId: $payment->created_by);
        } catch (\RuntimeException $e) {
            Log::error('Failed to auto-enroll after Paystack payment.', ['payment' => $payment->ref_id, 'error' => $e->getMessage()]);
            $payment->update(['status' => Payment::STATUS_FAILED]);
        }
    }

    protected function applyToPackage(Payment $payment): void
    {
        $package = SolarPackage::find($payment->document_id);
        $customer = $payment->customer_id ? \App\Models\Customer::find($payment->customer_id) : null;

        if (! $package || ! $customer) {
            Log::warning('Paystack payment could not be applied to package - missing package or customer.', [
                'payment' => $payment->ref_id,
                'package_id' => $payment->document_id,
                'customer_id' => $payment->customer_id,
            ]);
            $payment->update(['status' => Payment::STATUS_FAILED]);

            return;
        }

        if ($package->availability !== 'available') {
            Log::warning('Paystack payment for unavailable package.', ['payment' => $payment->ref_id]);
            $payment->update(['status' => Payment::STATUS_FAILED]);

            return;
        }

        // Create an order for the package purchase
        try {
            $orderService = app(\App\Services\OrderService::class);
            $order = $orderService->createOrder(
                data: [
                    'customer_name' => $customer->name,
                    'customer_phone' => $customer->phone,
                    'customer_email' => $customer->email,
                    'delivery_address' => $customer->address ?? 'Package purchase - delivery to be arranged',
                ],
                items: [[
                    'product_id' => null, // We'll create a special product or handle differently
                    'quantity' => 1,
                ]],
                userId: $payment->created_by,
            );

            // Since this is a package, we'll need to handle it differently
            // For now, mark payment as success and log it
            AuditLogger::log('created', 'package_payment', $payment->id, "Package payment {$payment->ref_id} for {$package->name} by {$customer->name} verified via Paystack", userId: $payment->created_by);
        } catch (\Throwable $e) {
            Log::error('Failed to create order for package payment.', ['payment' => $payment->ref_id, 'error' => $e->getMessage()]);
            $payment->update(['status' => Payment::STATUS_FAILED]);
        }
    }
}