<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Project;
use App\Models\Role;
use App\Models\SolarPackage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolarPackageFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->first()->id,
        ]);
    }

    public function test_can_create_solar_package_with_components(): void
    {
        $owner = $this->owner();

        $response = $this->actingAs($owner)
            ->post('/admin/solar-packages', [
                'name' => '5kVA Premium Solar Package',
                'description' => 'Complete home package',
                'package_price' => 3800000,
                'estimated_load_capacity' => '3.5kW',
                'inverter_capacity' => '5kva',
                'battery_capacity' => '200ah',
                'panel_count' => 6,
                'panel_wattage' => '550W',
                'warranty' => '2 Years',
                'is_featured' => 1,
                'availability' => 'available',
                'is_visible_online' => 1,
                'items' => [
                    [
                        'name' => '5kVA Hybrid Inverter',
                        'quantity' => 1,
                        'specification' => 'Pure sine wave 48V',
                    ],
                    [
                        'name' => '200Ah Lithium Battery',
                        'quantity' => 2,
                        'specification' => 'LiFePO4 Wall Mount',
                    ],
                    [
                        'name' => '550W Mono Solar Panels',
                        'quantity' => 6,
                        'specification' => 'Tier 1 Monocrystalline',
                    ],
                ],
            ]);

        $response->assertRedirect();

        $package = SolarPackage::first();
        $this->assertNotNull($package);
        $this->assertSame('5kVA Premium Solar Package', $package->name);
        $this->assertEquals(3800000.0, (float) $package->package_price);
        $this->assertCount(3, $package->items);

        $item = $package->items()->first();
        $this->assertNull($item->product_id);
        $this->assertSame('5kVA Hybrid Inverter', $item->name);
    }

    public function test_project_list_shows_customer_name_across_scopes(): void
    {
        $owner = $this->owner();

        $customer = Customer::create([
            'ref_id' => 'CUS-TEST-001',
            'name' => 'Chief Adebayo',
            'phone' => '08012345678',
            'email' => 'adebayo@example.com',
            'customer_type' => 'regular',
            'store_id' => null, // Unassigned or different store
        ]);

        $project = Project::create([
            'ref_id' => 'PRJ-TEST-100',
            'name' => 'Adebayo Solar Installation',
            'customer_id' => $customer->id,
            'contract_value' => 5000000,
            'status' => 'approved',
            'store_id' => 1,
        ]);

        $response = $this->actingAs($owner)
            ->withSession(['admin_store_id' => 1])
            ->get('/admin/projects');

        $response->assertOk();
        $response->assertSee('Chief Adebayo');
    }
}
