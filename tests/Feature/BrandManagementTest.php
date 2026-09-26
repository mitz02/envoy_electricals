<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BrandManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_brands_are_listed_with_product_counts_and_search(): void
    {
        $owner = $this->userForRole('owner');
        $brand = Brand::create(['name' => 'SunPower', 'slug' => 'sunpower', 'is_active' => true]);
        Brand::create(['name' => 'Luminous', 'slug' => 'luminous', 'is_active' => false]);

        Product::create([
            'ref_id' => 'BRANDM-0001',
            'sku' => 'BRANDM-SKU-1',
            'name' => 'SunPower Panel',
            'cost_price' => 100,
            'selling_price' => 150,
            'brand_id' => $brand->id,
        ]);

        $this->actingAs($owner)
            ->get(route('admin.brands.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Brands/Index')
                ->has('brands.data', 2)
                ->where('brands.data.0.name', 'Luminous')
                ->where('brands.data.0.products_count', 0)
                ->where('brands.data.1.name', 'SunPower')
                ->where('brands.data.1.products_count', 1)
            );

        $this->actingAs($owner)
            ->get(route('admin.brands.index', ['search' => 'Sun']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', 'Sun')
                ->has('brands.data', 1)
                ->where('brands.data.0.name', 'SunPower')
            );
    }

    public function test_a_brand_can_be_created_and_the_slug_is_generated(): void
    {
        $this->actingAs($this->userForRole('owner'))
            ->from(route('admin.brands.index'))
            ->post(route('admin.brands.store'), ['name' => 'Talesun Solar'])
            ->assertRedirect(route('admin.brands.index'))
            ->assertSessionHas('success', 'Brand added.');

        $this->assertDatabaseHas('brands', ['name' => 'Talesun Solar', 'slug' => 'talesun-solar']);
    }

    public function test_duplicate_brand_names_are_rejected(): void
    {
        Brand::create(['name' => 'SunPower', 'slug' => 'sunpower', 'is_active' => true]);

        $this->actingAs($this->userForRole('owner'))
            ->from(route('admin.brands.index'))
            ->post(route('admin.brands.store'), ['name' => 'SunPower'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Brand::where('name', 'SunPower')->count());
    }

    public function test_colliding_slugs_stay_unique(): void
    {
        Brand::create(['name' => 'Sun Power!', 'slug' => 'sunpower', 'is_active' => true]);

        $this->actingAs($this->userForRole('owner'))
            ->from(route('admin.brands.index'))
            ->post(route('admin.brands.store'), ['name' => 'Sun-Power'])
            ->assertRedirect(route('admin.brands.index'));

        $this->assertSame('sun-power', Brand::where('name', 'Sun-Power')->value('slug'));
        $this->assertNotSame('sunpower', Brand::where('name', 'Sun-Power')->value('slug'));
    }

    public function test_a_brand_can_be_edited(): void
    {
        $brand = Brand::create(['name' => 'Old Name', 'slug' => 'old-name', 'is_active' => true]);

        $this->actingAs($this->userForRole('owner'))
            ->from(route('admin.brands.index'))
            ->put(route('admin.brands.update', $brand), [
                'name' => 'New Name',
                'is_active' => false,
            ])
            ->assertRedirect(route('admin.brands.index'))
            ->assertSessionHas('success', 'Brand updated.');

        $brand->refresh();

        $this->assertSame('New Name', $brand->name);
        $this->assertSame('new-name', $brand->slug);
        $this->assertFalse($brand->is_active);
    }

    public function test_deleting_a_brand_leaves_its_products_in_place_without_a_brand(): void
    {
        $brand = Brand::create(['name' => 'Doomed', 'slug' => 'doomed', 'is_active' => true]);
        $product = Product::create([
            'ref_id' => 'BRANDM-0002',
            'sku' => 'BRANDM-SKU-2',
            'name' => 'Orphan Panel',
            'cost_price' => 100,
            'selling_price' => 150,
            'brand_id' => $brand->id,
        ]);

        $this->actingAs($this->userForRole('owner'))
            ->from(route('admin.brands.index'))
            ->delete(route('admin.brands.destroy', $brand))
            ->assertRedirect(route('admin.brands.index'))
            ->assertSessionHas('success', 'Brand deleted.');

        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'brand_id' => null]);
    }

    public function test_brand_detail_page_lists_products_and_total(): void
    {
        $brand = Brand::create(['name' => 'Detailed', 'slug' => 'detailed', 'is_active' => true]);

        Product::create([
            'ref_id' => 'BRANDM-0003',
            'sku' => 'BRANDM-SKU-3',
            'name' => 'Detailed Product',
            'cost_price' => 100,
            'selling_price' => 150,
            'brand_id' => $brand->id,
        ]);

        $this->actingAs($this->userForRole('owner'))
            ->get(route('admin.brands.show', $brand))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Brands/Show')
                ->where('brand.name', 'Detailed')
                ->where('brand.products_count', 1)
                ->where('brand.products.0.name', 'Detailed Product')
            );
    }

    public function test_a_user_without_product_permissions_cannot_reach_brands(): void
    {
        $trainee = $this->userForRole('trainee');
        $brand = Brand::create(['name' => 'Restricted', 'slug' => 'restricted', 'is_active' => true]);

        $this->actingAs($trainee)->get(route('admin.brands.index'))->assertForbidden();
        $this->actingAs($trainee)->get(route('admin.brands.show', $brand))->assertForbidden();
        $this->actingAs($trainee)->post(route('admin.brands.store'), ['name' => 'Nope'])->assertForbidden();
        $this->actingAs($trainee)->delete(route('admin.brands.destroy', $brand))->assertForbidden();
    }

    public function test_a_viewer_cannot_create_edit_or_delete_brands(): void
    {
        $role = Role::where('slug', 'sales')->firstOrFail();
        $viewer = User::factory()->create(['role_id' => $role->id]);
        $brand = Brand::create(['name' => 'Read Only', 'slug' => 'read-only', 'is_active' => true]);

        $this->actingAs($viewer)->get(route('admin.brands.index'))->assertOk();
        $this->actingAs($viewer)->post(route('admin.brands.store'), ['name' => 'Nope'])->assertForbidden();
        $this->actingAs($viewer)->put(route('admin.brands.update', $brand), ['name' => 'Changed'])->assertForbidden();
        $this->actingAs($viewer)->delete(route('admin.brands.destroy', $brand))->assertForbidden();
    }

    private function userForRole(string $slug): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', $slug)->firstOrFail()->id,
        ]);
    }
}
