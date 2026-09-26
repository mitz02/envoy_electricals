<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->first()->id,
        ]);
    }

    public function test_owner_can_view_settings(): void
    {
        $this->actingAs($this->owner())
            ->get('/admin/settings')
            ->assertOk();
    }

    public function test_user_without_permission_cannot_view_settings(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $staff = User::factory()->create([
            'role_id' => Role::where('slug', 'sales')->first()->id,
        ]);

        $this->actingAs($staff)
            ->get('/admin/settings')
            ->assertForbidden();
    }

    public function test_can_add_category(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->post('/admin/settings/categories', ['name' => 'Micro Inverters'])
            ->assertRedirect();

        $category = ProductCategory::where('name', 'Micro Inverters')->first();
        $this->assertNotNull($category);
        $this->assertSame('micro-inverters', $category->slug);
        $this->assertNull($category->parent_id);
    }

    public function test_json_add_returns_category_for_quick_add_modal(): void
    {
        $owner = $this->owner();

        $response = $this->actingAs($owner)
            ->postJson('/admin/settings/categories', ['name' => 'Batteries'])
            ->assertOk();

        $response->assertJsonStructure(['category' => ['id', 'name', 'slug', 'product_count']]);
        $this->assertSame('Batteries', $response->json('category.name'));
        $this->assertSame(0, $response->json('category.product_count'));
    }

    public function test_delete_category_leaves_products_uncategorised(): void
    {
        $owner = $this->owner();

        $category = ProductCategory::create(['name' => 'Solar Panels', 'slug' => 'solar-panels']);

        $product = Product::create([
            'ref_id' => 'PROD-TEST-2',
            'sku' => 'EV-SOL-DEL',
            'name' => 'Panel',
            'category_id' => $category->id,
            'cost_price' => 100,
            'selling_price' => 150,
        ]);

        $this->actingAs($owner)
            ->delete("/admin/settings/categories/{$category->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('product_categories', ['id' => $category->id]);

        $product->refresh();
        $this->assertNull($product->category_id);
    }

    public function test_duplicate_name_gets_unique_slug(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->post('/admin/settings/categories', ['name' => 'Accessories']);
        $this->actingAs($owner)->post('/admin/settings/categories', ['name' => 'Accessories']);

        $this->assertDatabaseCount('product_categories', 2);
        $this->assertDatabaseHas('product_categories', ['slug' => 'accessories']);
        $this->assertDatabaseHas('product_categories', ['slug' => 'accessories-2']);
    }
}
