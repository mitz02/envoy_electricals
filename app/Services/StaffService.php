<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\Payment;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StaffService
{
    public function create(array $data, int $userId): Staff
    {
        return DB::transaction(function () use ($data, $userId) {
            $staff = Staff::create([
                ...$data,
                'ref_id' => ReferenceGenerator::generate('staff'),
            ]);

            AuditLogger::log('created', 'staff', $staff->id, "Created staff {$staff->ref_id}: {$staff->name}");

            return $staff;
        });
    }

    public function update(Staff $staff, array $data, int $userId): Staff
    {
        $staff->update($data);

        AuditLogger::log('updated', 'staff', $staff->id, "Updated staff {$staff->ref_id}: {$staff->name}");

        return $staff;
    }

    public function destroy(Staff $staff, int $userId): void
    {
        $name = $staff->name;
        $staff->delete();

        AuditLogger::log('deleted', 'staff', $staff->id, "Deleted staff record for {$name}");
    }

    public function calculateAmountPaid(array $components): float
    {
        return round(
            (float) ($components['base_salary'] ?? 0)
            + (float) ($components['allowance'] ?? 0)
            + (float) ($components['bonus'] ?? 0)
            - (float) ($components['advance'] ?? 0)
            - (float) ($components['deduction'] ?? 0),
            2
        );
    }

    public function createPayroll(array $data, int $userId): Payroll
    {
        $amountPaid = $this->calculateAmountPaid($data);

        if ($amountPaid < 0) {
            throw new RuntimeException('Net pay cannot be negative.');
        }

        return DB::transaction(function () use ($data, $amountPaid, $userId) {
            $payroll = Payroll::create([
                ...$data,
                'ref_id' => ReferenceGenerator::generate('payroll'),
                'amount_paid' => $amountPaid,
                'status' => Payroll::STATUS_PENDING,
                'created_by' => $userId,
            ]);

            AuditLogger::log('created', 'payroll', $payroll->id, "Prepared payroll {$payroll->ref_id} for {$payroll->period}");

            return $payroll;
        });
    }

    public function updatePayroll(Payroll $payroll, array $data, int $userId): Payroll
    {
        if ($payroll->status !== Payroll::STATUS_PENDING) {
            throw new RuntimeException('Only pending payroll records can be edited.');
        }

        $data['amount_paid'] = $this->calculateAmountPaid($data);

        if ($data['amount_paid'] < 0) {
            throw new RuntimeException('Net pay cannot be negative.');
        }

        $payroll->update($data);

        AuditLogger::log('updated', 'payroll', $payroll->id, "Updated payroll {$payroll->ref_id} for {$payroll->period}");

        return $payroll;
    }

    public function markPaid(Payroll $payroll, array $data, int $userId): Payroll
    {
        if ($payroll->status !== Payroll::STATUS_PENDING) {
            throw new RuntimeException('Only pending payroll records can be paid.');
        }

        return DB::transaction(function () use ($payroll, $data, $userId) {
            $duplicate = Payroll::query()
                ->where('staff_id', $payroll->staff_id)
                ->where('period_year', $payroll->period_year)
                ->where('period_month', $payroll->period_month)
                ->where('status', Payroll::STATUS_PAID)
                ->where('id', '!=', $payroll->id)
                ->exists();

            if ($duplicate) {
                throw new RuntimeException('This staff member already has a paid payroll for the period.');
            }

            $payment = Payment::create([
                'ref_id' => ReferenceGenerator::generate('payment'),
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'amount' => $payroll->amount_paid,
                'payment_method' => $data['payment_method'] ?? null,
                'document_type' => 'payroll',
                'document_id' => $payroll->id,
                'reference' => $payroll->ref_id,
                'type' => 'payment_out',
                'status' => Payment::STATUS_SUCCESS,
                'gateway' => Payment::GATEWAY_LOCAL,
                'remarks' => "Payroll {$payroll->ref_id} ({$payroll->period}) — {$payroll->staff->name}",
                'created_by' => $userId,
            ]);

            $payroll->update([
                'status' => Payroll::STATUS_PAID,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'payment_method' => $data['payment_method'] ?? null,
            ]);

            AuditLogger::log('paid', 'payroll', $payroll->id, "Paid payroll {$payroll->ref_id} (₦{$payroll->amount_paid}) via {$payment->ref_id}");

            return $payroll;
        });
    }

    public function void(Payroll $payroll, int $userId): void
    {
        DB::transaction(function () use ($payroll, $userId) {
            if ($payroll->status === Payroll::STATUS_PAID) {
                $payroll->payments()->update(['status' => Payment::STATUS_REVERSED]);
            }

            $payroll->update([
                'status' => Payroll::STATUS_CANCELLED,
                'payment_date' => null,
                'payment_method' => null,
            ]);

            AuditLogger::log('cancelled', 'payroll', $payroll->id, "Cancelled payroll {$payroll->ref_id} for {$payroll->period}");
        });
    }
}