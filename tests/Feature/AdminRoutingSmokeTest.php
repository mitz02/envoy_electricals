<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoutingSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_admin_pages_render_for_owner(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->first()->id,
        ]);

        $paths = [
            '/admin',
            '/admin/products',
            '/admin/products/create',
            '/admin/sales',
            '/admin/sales/create',
            '/admin/purchases',
            '/admin/purchases/create',
            '/admin/customers',
            '/admin/customers/create',
            '/admin/suppliers',
            '/admin/expenses',
            '/admin/expenses/create',
            '/admin/payments',
            '/admin/stock/movements',
            '/admin/projects',
            '/admin/projects/create',
            '/admin/reports/projects',
            '/admin/staff',
            '/admin/staff/create',
            '/admin/payroll',
            '/admin/payroll/create',
            '/admin/reports/payroll',
            '/admin/reports/sales',
            '/admin/reports/purchases',
            '/admin/reports/inventory',
            '/admin/reports/profit',
            '/admin/reports/expenses',
            '/admin/audit-logs',
        ];

        foreach ($paths as $path) {
            $this->actingAs($owner)->get($path)->assertOk();
        }
    }

    public function test_report_routes_tolerate_null_querystring_filters(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->first()->id,
        ]);

        $paths = [
            '/admin/reports/sales?from=null&to=null&group=week',
            '/admin/reports/sales?from=null&to=null&group=month',
            '/admin/reports/purchases?from=null&to=null',
            '/admin/reports/expenses?from=null&to=null',
            '/admin/reports/profit?from=null&to=null',
            '/admin/reports/projects?from=null&to=null',
            '/admin/reports/export?type=sales&from=null&to=null',
        ];

        foreach ($paths as $path) {
            $this->actingAs($owner)->get($path)->assertOk();
        }
    }
}
