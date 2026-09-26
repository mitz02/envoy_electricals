<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use App\Services\StaffService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        if (Staff::count() > 0) {
            return;
        }

        DB::transaction(function () {
            $staffService = app(StaffService::class);

            $ada = User::where('email', 'ada@envoyelectric.com')->first();
            $mark = User::where('email', 'mark@envoyelectric.com')->first();
            $user = $ada ?? User::where('email', 'owner@envoyelectric.com')->first();

            $managerRole = Role::where('slug', 'manager')->first();
            $technicianRole = Role::where('slug', 'technician')->first();
            $accountantRole = Role::where('slug', 'accountant')->first();

            // Demo staff profiles — Ada and Mark link to their existing login
            // accounts when present, so credentials are only supplied as a
            // fallback for a fresh database.
            $adaStaff = $staffService->create([
                'user_id' => $ada?->id,
                'name' => 'Ada Manager',
                'position' => 'Operations Manager',
                'phone' => '+234 802 222 3333',
                'email' => 'ada@envoyelectric.com',
                'password' => 'envoy123',
                'role_id' => $managerRole?->id,
                'date_joined' => now()->subYears(2)->toDateString(),
                'base_salary' => 350000,
                'housing_allowance' => 70000,
                'transport_allowance' => 30000,
                'other_allowance' => 10000,
                'is_active' => true,
            ], $user->id);

            $markStaff = $staffService->create([
                'user_id' => $mark?->id,
                'name' => 'Mark Technician',
                'position' => 'Senior Technician',
                'phone' => '+234 803 444 5555',
                'email' => 'mark@envoyelectric.com',
                'password' => 'envoy123',
                'role_id' => $technicianRole?->id,
                'date_joined' => now()->subYear()->toDateString(),
                'base_salary' => 200000,
                'housing_allowance' => 30000,
                'transport_allowance' => 15000,
                'other_allowance' => 5000,
                'is_active' => true,
            ], $user->id);

            $tunde = $staffService->create([
                'name' => 'Tunde Electrician',
                'position' => 'Electrician',
                'phone' => '+234 805 666 7777',
                'email' => 'tunde@envoyelectric.com',
                'password' => 'envoy123',
                'role_id' => $technicianRole?->id,
                'date_joined' => now()->subMonths(8)->toDateString(),
                'base_salary' => 120000,
                'housing_allowance' => 20000,
                'transport_allowance' => 10000,
                'is_active' => true,
            ], $user->id);

            $blessing = $staffService->create([
                'name' => 'Blessing Sales',
                'position' => 'Sales Attendant',
                'phone' => '+234 807 888 9990',
                'email' => 'blessing@envoyelectric.com',
                'password' => 'envoy123',
                'role_id' => $accountantRole?->id,
                'date_joined' => now()->subMonths(14)->toDateString(),
                'base_salary' => 100000,
                'housing_allowance' => 15000,
                'is_active' => true,
            ], $user->id);

            // Spec §28 payroll example for Mark's current month — net pay ₦210,000.
            $markPayroll = $staffService->createPayroll([
                'staff_id' => $markStaff->id,
                'period_month' => now()->format('n'),
                'period_year' => now()->format('Y'),
                'base_salary' => 200000,
                'allowance' => 30000,
                'bonus' => 20000,
                'advance' => 30000,
                'deduction' => 10000,
                'notes' => 'Dem-demo payroll with spec §28 numbers.',
            ], $user->id);

            $staffService->markPaid($markPayroll, [
                'payment_date' => now()->toDateString(),
                'payment_method' => 'bank',
            ], $user->id);

            // Pending payroll for Ada this month.
            $staffService->createPayroll([
                'staff_id' => $adaStaff->id,
                'period_month' => now()->format('n'),
                'period_year' => now()->format('Y'),
                'base_salary' => $adaStaff->base_salary,
                'allowance' => $adaStaff->total_allowance,
                'bonus' => 0,
                'advance' => 0,
                'deduction' => 0,
            ], $user->id);

            // Historical payroll for Tunde last month, already paid.
            $tundePayroll = $staffService->createPayroll([
                'staff_id' => $tunde->id,
                'period_month' => now()->subMonth()->format('n'),
                'period_year' => now()->subMonth()->format('Y'),
                'base_salary' => 120000,
                'allowance' => 30000,
                'bonus' => 10000,
                'advance' => 0,
                'deduction' => 0,
            ], $user->id);

            $staffService->markPaid($tundePayroll, [
                'payment_date' => now()->subDay()->toDateString(),
                'payment_method' => 'cash',
            ], $user->id);
        });
    }
}
