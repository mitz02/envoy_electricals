<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_delete_a_product(): void
    {
        $product = $this->product('OWNER-DELETE');

        $this->actingAs($this->userForRole('owner'))
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect()
            ->assertSessionHas('success', 'Product deleted.');

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_owner_can_bulk_delete_selected_products(): void
    {
        $first = $this->product('BULK-FIRST');
        $second = $this->product('BULK-SECOND');
        $remaining = $this->product('BULK-REMAINING');

        $this->actingAs($this->userForRole('owner'))
            ->post(route('admin.products.bulk-delete'), [
                'ids' => [$first->id, $second->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Deleted 2 product(s).');

        $this->assertSoftDeleted('products', ['id' => $first->id]);
        $this->assertSoftDeleted('products', ['id' => $second->id]);
        $this->assertNotSoftDeleted('products', ['id' => $remaining->id]);
    }

    public function test_user_without_delete_permission_cannot_delete_products(): void
    {
        $product = $this->product('FORBIDDEN-DELETE');
        $user = $this->userForRole('manager');

        $this->actingAs($user)
            ->delete(route('admin.products.destroy', $product))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.products.bulk-delete'), [
                'ids' => [$product->id],
            ])
            ->assertForbidden();

        $this->assertNotSoftDeleted('products', ['id' => $product->id]);
    }

    private function userForRole(string $slug): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('slug', $slug)->firstOrFail()->id,
        ]);
    }

    private function product(string $sku): Product
    {
        return Product::create([
            'ref_id' => "PROD-{$sku}",
            'sku' => $sku,
            'name' => "Test {$sku}",
            'cost_price' => 100,
            'selling_price' => 150,
        ]);
    }
}
