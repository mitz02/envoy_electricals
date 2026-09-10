<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Sale;
use App\Services\AuditLogger;
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
        protected SaleService $saleService
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
}