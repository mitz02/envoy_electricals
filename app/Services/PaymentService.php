<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Store;

class PaymentService
{
    /**
     * Record a payment and return it. Local (staff-entered) payments are
     * recorded as already successful; Paystack flows create a pending payment
     * first and mark it successful only after server-side webhook verification.
     */
    public static function recordPayment(
        string $type,
        float $amount,
        ?string $paymentMethod,
        ?string $documentType,
        ?int $documentId,
        ?int $customerId,
        ?int $supplierId,
        ?string $reference,
        ?int $userId,
        string $gateway = Payment::GATEWAY_LOCAL,
        ?string $gatewayReference = null,
        string $status = Payment::STATUS_SUCCESS,
        ?string $remarks = null,
        ?string $paymentDate = null,
        ?int $traineeId = null,
        ?int $storeId = null,
    ): ?Payment {
        if ($amount <= 0) {
            return null;
        }

        $resolvedStoreId = $storeId
            ?? session('admin_store_id')
            ?? auth()->user()?->store_id
            ?? Store::where('is_default', true)->value('id')
            ?? Store::value('id')
            ?? 1;

        $refId = ReferenceGenerator::generate('payment').'-'.bin2hex(random_bytes(4));

        return Payment::create([
            'ref_id' => $refId,
            'payment_date' => $paymentDate ?? now()->toDateString(),
            'amount' => $amount,
            'payment_method' => $paymentMethod ?? ($gateway === Payment::GATEWAY_PAYSTACK ? 'paystack' : null),
            'document_type' => $documentType,
            'document_id' => $documentId,
            'store_id' => $resolvedStoreId,
            // Enrollment payments track the trainee, not a customer record.
            'customer_id' => $traineeId !== null ? null : $customerId,
            'trainee_id' => $traineeId ?? null,
            'supplier_id' => $supplierId,
            'type' => $type,
            'reference' => $reference ?? $refId,
            'status' => $status,
            'gateway' => $gateway,
            'gateway_reference' => $gatewayReference,
            'paid_at' => $status === Payment::STATUS_SUCCESS ? now() : null,
            'remarks' => $remarks,
            'created_by' => $userId,
        ]);
    }
}
