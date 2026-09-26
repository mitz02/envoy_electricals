<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductBrandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_products_table_exposes_the_brand_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('products', 'brand_id'));
    }

    public function test_a_product_can_be_created_with_a_brand(): void
    {
        $owner = $this->owner();
        $brand = Brand::create(['name' => 'SunPower', 'slug' => 'sunpower', 'is_active' => true]);
        $store = $this->store();

        $this->actingAs($owner)
            ->post(route('admin.products.store'), [
                'name' => 'Panasonic Vertex',
                'category_id' => null,
                'brand_id' => $brand->id,
                'store_id' => $store->id,
                'cost_price' => 4000,
                'selling_price' => 3000,
            ])
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Panasonic Vertex')->firstOrFail();

        $this->assertSame($brand->id, (int) $product->brand_id);
        $this->assertSame('SunPower', $product->brand->name);
    }

    public function test_a_product_can_be_created_without_a_brand(): void
    {
        $owner = $this->owner();
        $store = $this->store();

        $this->actingAs($owner)
            ->post(route('admin.products.store'), [
                'name' => 'Generic Panel',
                'store_id' => $store->id,
                'cost_price' => 2000,
                'selling_price' => 2500,
            ])
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Generic Panel')->firstOrFail();

        $this->assertNull($product->brand_id);
        $this->assertNull($product->brand);
    }

    public function test_an_unknown_brand_is_rejected(): void
    {
        $owner = $this->owner();
        $store = $this->store();

        $this->actingAs($owner)
            ->post(route('admin.products.store'), [
                'name' => 'Bad Brand Panel',
                'brand_id' => 999999,
                'store_id' => $store->id,
                'cost_price' => 2000,
                'selling_price' => 2500,
            ])
            ->assertSessionHasErrors('brand_id');

        $this->assertDatabaseMissing('products', ['name' => 'Bad Brand Panel']);
    }

    public function test_brand_is_exposed_as_a_relation_object_in_the_list_payload(): void
    {
        $owner = $this->owner();
        $brand = Brand::create(['name' => 'Luminous', 'slug' => 'luminous', 'is_active' => true]);
        $product = Product::create([
            'ref_id' => 'BRAND-0001',
            'name' => 'Luminous Topper',
            'sku' => 'BRAND-SKU-1',
            'cost_price' => 100,
            'selling_price' => 150,
            'brand_id' => $brand->id,
        ]);

        $this->actingAs($owner)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Products/Index')
                ->where('products.data.0.id', $product->id)
                ->where('products.data.0.brand.id', $brand->id)
                ->where('products.data.0.brand.name', 'Luminous')
            );
    }

    public function test_bulk_brand_assignment_updates_products(): void
    {
        $owner = $this->owner();
        $brand = Brand::create(['name' => 'Talesun', 'slug' => 'talesun', 'is_active' => true]);
        $product = Product::create([
            'ref_id' => 'BRAND-0002',
            'name' => 'Unbranded Panel',
            'sku' => 'BRAND-SKU-2',
            'cost_price' => 100,
            'selling_price' => 150,
        ]);

        $this->actingAs($owner)
            ->from(route('admin.products.index'))
            ->post(route('admin.products.bulk-brand'), [
                'ids' => [$product->id],
                'brand_id' => $brand->id,
            ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertSame($brand->id, (int) $product->fresh()->brand_id);
    }

    public function test_deleting_a_brand_leaves_the_product_without_one(): void
    {
        $owner = $this->owner();
        $brand = Brand::create(['name' => 'Temporary Brand', 'slug' => 'temporary', 'is_active' => true]);
        $product = Product::create([
            'ref_id' => 'BRAND-0003',
            'name' => 'Orphan Panel',
            'sku' => 'BRAND-SKU-3',
            'cost_price' => 100,
            'selling_price' => 150,
            'brand_id' => $brand->id,
        ]);

        $brand->delete();

        $product->refresh();

        $this->assertNull($product->brand_id, 'deleting a brand must null the product reference');
        $this->assertTrue($product->exists);
    }

    private function owner(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->firstOrFail()->id,
        ]);
    }

    private function store(): Store
    {
        return Store::create([
            'name' => 'Brand Test Store',
            'code' => 'BRAND-TEST',
            'is_active' => true,
            'is_default' => true,
        ]);
    }
}
