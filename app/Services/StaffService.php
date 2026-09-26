<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class StaffService
{
    public function create(array $data, int $userId): Staff
    {
        return DB::transaction(function () use ($data) {
            $user = null;

            if (! empty($data['user_id'])) {
                $user = User::find($data['user_id']);
            }

            if ($user === null) {
                if (empty($data['password']) || empty($data['role_id'])) {
                    throw new InvalidArgumentException(
                        'A password and role_id are required to create the staff login account.'
                    );
                }

                $role = Role::find($data['role_id']);
                $allowedStoreIds = $role?->allowedStoreIds() ?? [];

                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'role_id' => $data['role_id'],
                    'is_active' => true,
                    'store_id' => $allowedStoreIds[0] ?? null,
                ]);

                app(UserMailService::class)->newAccount($user, $data['password'], 'staff');
            }

            // Sync store access based on role's allowed_store_ids
            $this->syncStoreAccessFromRole($user, $data);

            // Whitelist the fields explicitly — the caller array may also carry
            // login-only keys (password, role_id) that must never land on the
            // staff row, and seeding runs with mass assignment unguarded.
            $staff = Staff::create([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'],
                'date_joined' => $data['date_joined'] ?? null,
                'base_salary' => (float) ($data['base_salary'] ?? 0),
                'housing_allowance' => (float) ($data['housing_allowance'] ?? 0),
                'transport_allowance' => (float) ($data['transport_allowance'] ?? 0),
                'other_allowance' => (float) ($data['other_allowance'] ?? 0),
                'is_active' => $data['is_active'] ?? true,
                'notes' => $data['notes'] ?? null,
                'user_id' => $user->id,
                'ref_id' => ReferenceGenerator::generate('staff'),
            ]);

            AuditLogger::log('created', 'user', $user->id, "Created login account for staff {$staff->ref_id}: {$staff->name} ({$user->email})");
            AuditLogger::log('created', 'staff', $staff->id, "Created staff {$staff->ref_id}: {$staff->name}");

            return $staff;
        });
    }

    public function update(Staff $staff, array $data, int $userId): Staff
    {
        $this->syncLoginAccount($staff, $data);

        $staff->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
            'date_joined' => $data['date_joined'] ?? null,
            'base_salary' => $data['base_salary'],
            'housing_allowance' => (float) ($data['housing_allowance'] ?? 0),
            'transport_allowance' => (float) ($data['transport_allowance'] ?? 0),
            'other_allowance' => (float) ($data['other_allowance'] ?? 0),
            'is_active' => $data['is_active'] ?? $staff->is_active,
            'notes' => $data['notes'] ?? null,
        ]);

        AuditLogger::log('updated', 'staff', $staff->id, "Updated staff {$staff->ref_id}: {$staff->name}");

        return $staff;
    }

    protected function syncLoginAccount(Staff $staff, array $data): void
    {
        if (! $staff->user) {
            return;
        }

        $userData = [];

        if (array_key_exists('name', $data)) {
            $userData['name'] = $data['name'];
        }

        if (array_key_exists('email', $data)) {
            $userData['email'] = $data['email'];
        }

        if (! empty($data['password'])) {
            $userData['password'] = $data['password'];
        }

        if (array_key_exists('role_id', $data) && $data['role_id']) {
            $userData['role_id'] = $data['role_id'];
        }

        if ($userData) {
            $staff->user->update($userData);

            AuditLogger::log('updated', 'user', $staff->user->id, "Updated login account for staff {$staff->ref_id}: {$staff->name} ({$staff->user->email})");
        }

        // Sync store access based on role's allowed_store_ids
        $this->syncStoreAccessFromRole($staff->user, $data);
    }

    /**
     * Sync the user's store access based on their role's allowed_store_ids.
     * This ensures store access is tied to the role, not set individually per staff.
     */
    protected function syncStoreAccessFromRole(User $user, array $data): void
    {
        // Get the role_id from data or user
        $roleId = $data['role_id'] ?? $user->role_id ?? null;

        if (! $roleId) {
            return;
        }

        $role = Role::find($roleId);

        if (! $role) {
            return;
        }

        $allowedStoreIds = $role->allowedStoreIds();

        // If role has no store restrictions, user sees all branches
        if ($allowedStoreIds === null) {
            // User is unrestricted - clear any store restrictions
            if ($user->stores()->exists()) {
                $user->allowedStores()->detach();

                AuditLogger::log(
                    'updated',
                    'user',
                    $user->id,
                    "Set branch access for {$user->email} to: all branches (role has no restrictions)"
                );
            }

            return;
        }

        // Role has specific allowed stores - sync user to match
        $currentStoreIds = $user->stores()->pluck('stores.id')->map(fn ($id) => (int) $id)->all();

        if ($allowedStoreIds === $currentStoreIds) {
            return; // Already in sync
        }

        $user->syncAllowedStores($allowedStoreIds);

        $names = $allowedStoreIds === []
            ? 'all branches'
            : Store::whereIn('id', $allowedStoreIds)->orderBy('name')->pluck('name')->implode(', ');

        AuditLogger::log(
            'updated',
            'user',
            $user->id,
            "Set branch access for {$user->email} to: {$names} (via role {$role->name})"
        );
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
        DB::transaction(function () use ($payroll) {
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
