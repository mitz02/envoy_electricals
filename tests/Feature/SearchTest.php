<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->first()->id,
        ]);
    }

    public function test_admin_search_returns_matching_products(): void
    {
        $owner = $this->owner();

        Product::create([
            'ref_id' => 'PROD-SEARCH-1',
            'sku' => 'EV-MCB-20',
            'name' => 'MCB 20A Breaker',
            'cost_price' => 1000,
            'selling_price' => 1500,
        ]);

        $response = $this->actingAs($owner)
            ->getJson('/admin/search?q=breaker')
            ->assertOk();

        $products = collect($response->json('groups'))->firstWhere('key', 'products');
        $this->assertNotNull($products);
        $this->assertSame('MCB 20A Breaker', $products['items'][0]['label']);
        $this->assertSame('/admin/products/'.Product::first()->id.'/edit', $products['items'][0]['href']);
    }

    public function test_short_query_returns_no_results(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->getJson('/admin/search?q=a')
            ->assertOk()
            ->assertJson(['groups' => []]);
    }

    public function test_results_are_filtered_by_permission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $manager = User::factory()->create([
            'role_id' => Role::where('slug', 'manager')->first()->id,
        ]);

        Customer::create([
            'ref_id' => 'CUST-SEARCH-1',
            'name' => 'Ade Bakare',
            'phone' => '08055555555',
        ]);

        $ownerResponse = $this->actingAs($this->owner())->getJson('/admin/search?q=ade')->assertOk();
        $this->assertNotNull(collect($ownerResponse->json('groups'))->firstWhere('key', 'customers'));

        $managerResponse = $this->actingAs($manager)->getJson('/admin/search?q=ade')->assertOk();
        $this->assertNull(collect($managerResponse->json('groups'))->firstWhere('key', 'customers'));
    }

    public function test_user_without_dashboard_access_cannot_search(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $technician = User::factory()->create([
            'role_id' => Role::where('slug', 'technician')->first()->id,
        ]);

        $this->actingAs($technician)
            ->getJson('/admin/search?q=ade')
            ->assertForbidden();
    }
}
