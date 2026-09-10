<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use App\Services\StaffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): User
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->first()->id,
        ]);
    }

    protected function userWithRole(string $slug): User
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('slug', $slug)->first()->id,
        ]);
    }

    protected function staff(int $userId, array $overrides = []): Staff
    {
        return app(StaffService::class)->create(array_merge([
            'name' => 'Test Technician',
            'position' => 'Technician',
            'base_salary' => 200000,
        ], $overrides), $userId);
    }

    protected function payroll(int $userId, array $components = []): \App\Models\Payroll
    {
        $staff = $this->staff($userId, ['name' => 'Payroll Staff']);

        return app(StaffService::class)->createPayroll(array_merge([
            'staff_id' => $staff->id,
            'period_month' => now()->format('n'),
            'period_year' => now()->format('Y'),
            'base_salary' => 200000,
            'allowance' => 30000,
            'bonus' => 0,
            'advance' => 0,
            'deduction' => 0,
        ], $components), $userId);
    }

    public function test_create_staff_generates_ref_and_stores_allowances(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->post('/admin/staff', [
                'name' => 'Ada Manager',
                'position' => 'Operations Manager',
                'email' => 'ada@staff.test',
                'phone' => '08012345678',
                'date_joined' => now()->toDateString(),
                'base_salary' => 350000,
                'housing_allowance' => 70000,
                'transport_allowance' => 30000,
                'other_allowance' => 10000,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('staff', [
            'name' => 'Ada Manager',
            'ref_id' => 'STF-' . now()->format('Y') . '-000001',
            'base_salary' => 350000,
            'housing_allowance' => 70000,
        ]);

        $staff = Staff::where('name', 'Ada Manager')->first();
        $this->assertSame(110000.0, (float) $staff->total_allowance);

        $this->actingAs($owner)->get("/admin/staff/{$staff->id}")->assertOk();
    }

    public function test_payroll_create_uses_spec_net_pay_calculation(): void
    {
        $owner = $this->owner();
        $staff = $this->staff($owner->id);

        // Spec §28: base 200,000 + allowance 30,000 + bonus 20,000 − advance 30,000 − deduction 10,000 = 210,000.
        $this->actingAs($owner)
            ->post('/admin/payroll', [
                'staff_id' => $staff->id,
                'period_month' => now()->format('n'),
                'period_year' => now()->format('Y'),
                'base_salary' => 200000,
                'allowance' => 30000,
                'bonus' => 20000,
                'advance' => 30000,
                'deduction' => 10000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payroll', [
            'staff_id' => $staff->id,
            'base_salary' => 200000,
            'allowance' => 30000,
            'bonus' => 20000,
            'advance' => 30000,
            'deduction' => 10000,
            'amount_paid' => 210000,
            'status' => 'pending',
        ]);

        $payroll = $staff->payrolls()->first();
        $this->assertStringStartsWith('PRL-' . now()->format('Y') . '-', $payroll->ref_id);
    }

    public function test_negative_net_pay_is_rejected_server_side(): void
    {
        $owner = $this->owner();
        $staff = $this->staff($owner->id);

        $this->from('/admin/payroll')
            ->actingAs($owner)
            ->post('/admin/payroll', [
                'staff_id' => $staff->id,
                'period_month' => now()->format('n'),
                'period_year' => now()->format('Y'),
                'base_salary' => 100000,
                'allowance' => 0,
                'advance' => 200000,
                'deduction' => 50000,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, $staff->payrolls()->count());
    }

    public function test_mark_paid_records_payment_ledger_and_blocks_paying_twice(): void
    {
        $owner = $this->owner();
        $payroll = $this->payroll($owner->id);

        $this->actingAs($owner)
            ->post("/admin/payroll/{$payroll->id}/pay", [
                'payment_method' => 'bank',
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $payroll->refresh();
        $this->assertSame('paid', $payroll->status);
        $this->assertSame('bank', $payroll->payment_method);

        $this->assertDatabaseHas('payments', [
            'document_type' => 'payroll',
            'document_id' => $payroll->id,
            'amount' => 230000,
            'type' => 'payment_out',
            'status' => Payment::STATUS_SUCCESS,
            'gateway' => Payment::GATEWAY_LOCAL,
            'reference' => $payroll->ref_id,
        ]);

        $this->from("/admin/payroll/{$payroll->id}")
            ->actingAs($owner)
            ->post("/admin/payroll/{$payroll->id}/pay", ['payment_method' => 'cash'])
            ->assertRedirect()
            ->assertSessionHasErrors('pay');

        $this->assertSame(1, $payroll->payments()->count());
        $this->assertSame('paid', $payroll->fresh()->status);
    }

    public function test_cannot_pay_a_second_payroll_for_same_staff_period(): void
    {
        $owner = $this->owner();
        $staff = $this->staff($owner->id, ['name' => 'Second Payroll']);

        $first = app(StaffService::class)->createPayroll([
            'staff_id' => $staff->id,
            'period_month' => now()->format('n'),
            'period_year' => now()->format('Y'),
            'base_salary' => 100000,
        ], $owner->id);
        app(StaffService::class)->markPaid($first, ['payment_method' => 'cash'], $owner->id);

        $second = app(StaffService::class)->createPayroll([
            'staff_id' => $staff->id,
            'period_month' => now()->format('n'),
            'period_year' => now()->format('Y'),
            'base_salary' => 150000,
        ], $owner->id);

        $this->from("/admin/payroll/{$second->id}")
            ->actingAs($owner)
            ->post("/admin/payroll/{$second->id}/pay", ['payment_method' => 'bank'])
            ->assertRedirect()
            ->assertSessionHasErrors('pay');

        $this->assertSame('pending', $second->fresh()->status);
    }

    public function test_void_paid_payroll_cancels_it_and_reverses_payment(): void
    {
        $owner = $this->owner();
        $payroll = $this->payroll($owner->id);
        app(StaffService::class)->markPaid($payroll, ['payment_method' => 'bank'], $owner->id);

        $this->actingAs($owner)
            ->delete("/admin/payroll/{$payroll->id}")
            ->assertRedirect();

        $payroll->refresh();
        $this->assertSame('cancelled', $payroll->status);
        $this->assertNull($payroll->payment_date);
        $this->assertSame(Payment::STATUS_REVERSED, $payroll->payments()->first()->status);
    }

    public function test_staff_show_aggregates_bonuses_advances_and_deductions(): void
    {
        $owner = $this->owner();
        $staff = $this->staff($owner->id);

        $one = $this->payroll($owner->id, [
            'staff_id' => $staff->id,
            'period_month' => now()->format('n'),
            'base_salary' => 200000,
            'bonus' => 20000,
            'advance' => 30000,
            'deduction' => 10000,
        ]);
        app(StaffService::class)->markPaid($one, ['payment_method' => 'bank'], $owner->id);

        $staff = $staff->fresh();
        $this->assertEquals(210000.0, (float) $staff->total_paid);
        $this->assertEquals(20000.0, (float) $staff->total_bonuses);
        $this->assertEquals(30000.0, (float) $staff->total_advances);
        $this->assertEquals(10000.0, (float) $staff->total_deductions);

        $this->actingAs($owner)->get("/admin/staff/{$staff->id}")->assertOk();
    }

    public function test_staff_and_payroll_permissions(): void
    {
        $owner = $this->owner();

        $technician = $this->userWithRole('technician');
        $this->actingAs($technician)->get('/admin/staff')->assertForbidden();
        $this->actingAs($technician)->get('/admin/payroll')->assertForbidden();

        $sales = $this->userWithRole('sales');
        $this->actingAs($sales)->get('/admin/staff')->assertForbidden();

        $accountant = $this->userWithRole('accountant');
        $this->actingAs($accountant)->get('/admin/staff')->assertOk();
        $this->actingAs($accountant)->get('/admin/payroll')->assertOk();
        $this->actingAs($accountant)->get('/admin/payroll/create')->assertOk();

        $manager = $this->userWithRole('manager');
        $this->actingAs($manager)->get('/admin/staff')->assertOk();
        $this->actingAs($manager)->get('/admin/payroll')->assertOk();
        $this->actingAs($manager)->get('/admin/staff/create')->assertForbidden();
    }
}