<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use App\Support\StoreAccess;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StoreAccessTest extends TestCase
{
    use RefreshDatabase;

    private Store $lagos;

    private Store $ibadan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        // A default branch is created by the inventory backfill migration, so
        // use distinct codes to avoid colliding with it.
        $this->lagos = Store::create(['name' => 'Test Lagos', 'code' => 'TLAG', 'is_active' => true, 'is_default' => true]);
        $this->ibadan = Store::create(['name' => 'Test Ibadan', 'code' => 'TIBA', 'is_active' => true, 'is_default' => false]);
    }

    private function userWithStores(string $roleSlug, array $storeIds): User
    {
        $user = User::factory()->create([
            'role_id' => Role::where('slug', $roleSlug)->firstOrFail()->id,
        ]);

        $user->syncAllowedStores($storeIds);

        return $user->fresh();
    }

    public function test_a_staff_login_only_sees_the_branches_it_was_granted(): void
    {
        $user = $this->userWithStores('sales', [$this->lagos->id]);

        $this->assertSame([$this->lagos->id], $user->allowedStoreIds());
        $this->assertTrue($user->isStoreRestricted());
        $this->assertTrue($user->canAccessStore($this->lagos->id));
        $this->assertFalse($user->canAccessStore($this->ibadan->id));
    }

    public function test_a_login_with_no_granted_branches_sees_everything(): void
    {
        $user = $this->userWithStores('sales', []);

        $this->assertNull($user->allowedStoreIds());
        $this->assertFalse($user->isStoreRestricted());
        $this->assertTrue($user->canAccessStore($this->ibadan->id));
    }

    public function test_a_super_admin_can_never_be_locked_out(): void
    {
        $owner = User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->firstOrFail()->id,
        ]);

        $owner->syncAllowedStores([$this->lagos->id]);

        $this->assertNull($owner->allowedStoreIds());
        $this->assertFalse($owner->isStoreRestricted());
        $this->assertTrue($owner->canAccessStore($this->ibadan->id));
    }

    public function test_the_store_dropdown_only_offers_granted_branches(): void
    {
        $user = $this->userWithStores('sales', [$this->lagos->id]);

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Products/Index')
                ->has('stores', 1)
                ->where('stores.0.name', 'Test Lagos')
            );
    }

    public function test_a_forged_branch_selection_is_rejected(): void
    {
        $user = $this->userWithStores('sales', [$this->lagos->id]);

        $this->actingAs($user)
            ->from(route('admin.products.index'))
            ->post(route('admin.store.select'), ['store_id' => $this->ibadan->id])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('error', 'You do not have access to that branch.');

        $this->assertNull(session('admin_store_id'));
    }

    public function test_selecting_a_granted_branch_is_allowed(): void
    {
        $user = $this->userWithStores('sales', [$this->lagos->id]);

        $this->actingAs($user)
            ->from(route('admin.products.index'))
            ->post(route('admin.store.select'), ['store_id' => $this->lagos->id])
            ->assertSessionHas('success', 'Branch changed.');

        $this->assertSame($this->lagos->id, session('admin_store_id'));
    }

    public function test_sales_from_a_locked_branch_are_hidden(): void
    {
        $user = $this->userWithStores('sales', [$this->lagos->id]);

        Sale::create([
            'ref_id' => 'SAL-ACCESS-1',
            'invoice_no' => 'ACC-INV-1',
            'sale_date' => now()->toDateString(),
            'total' => 1000,
            'store_id' => $this->lagos->id,
            'user_id' => $user->id,
        ]);

        Sale::create([
            'ref_id' => 'SAL-ACCESS-2',
            'invoice_no' => 'ACC-INV-2',
            'sale_date' => now()->toDateString(),
            'total' => 2000,
            'store_id' => $this->ibadan->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->get(route('admin.sales.index'))->assertOk();

        $this->assertTrue(Sale::where('ref_id', 'SAL-ACCESS-1')->exists());
        $this->assertFalse(
            Sale::withoutGlobalScope('store')->where('ref_id', 'SAL-ACCESS-2')->first()->store_id === null
        );

        // The locked branch's sale must not appear in the scoped query.
        $this->assertSame(
            ['SAL-ACCESS-1'],
            Sale::pluck('ref_id')->all()
        );
    }

    public function test_products_only_in_a_locked_branch_are_not_listed(): void
    {
        $user = $this->userWithStores('sales', [$this->lagos->id]);

        $lagosProduct = $this->productWithStore('Visible Panel', $this->lagos);
        $ibadanProduct = $this->productWithStore('Hidden Panel', $this->ibadan);

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Visible Panel')
            );

        $this->assertNotNull($lagosProduct);
        $this->assertNotNull($ibadanProduct);
    }

    public function test_a_locked_user_cannot_post_a_stock_adjustment_into_another_branch(): void
    {
        $user = $this->userWithStores('sales', [$this->lagos->id]);
        $user->directPermissions()->attach(
            Permission::where('slug', 'inventory.adjust')->firstOrFail()->id
        );

        $product = $this->productWithStore('Guarded Panel', $this->ibadan);

        $this->actingAs($user)
            ->from(route('admin.stock.adjust'))
            ->post(route('admin.stock.adjust.submit'), [
                'product_id' => $product->id,
                'quantity' => 5,
                'type' => 'adjustment',
                'direction' => 'increase',
                'reason' => 'Sneaky correction',
                'store_id' => $this->ibadan->id,
            ])
            ->assertSessionHasErrors('store_id');

        $this->assertDatabaseMissing('stock_movements', ['product_id' => $product->id]);
    }

    public function test_a_super_admin_can_lock_a_staff_login_down_from_the_staff_form(): void
    {
        $owner = User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->firstOrFail()->id,
        ]);

        $salesRole = Role::where('slug', 'sales')->firstOrFail();
        $salesRole->forceFill([
            'access_config' => [
                'allowed_store_ids' => [$this->lagos->id],
            ],
        ])->save();

        $this->actingAs($owner)
            ->post(route('admin.staff.store'), [
                'name' => 'Ada Branch',
                'email' => 'ada.branch@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'role_id' => $salesRole->id,
                'base_salary' => 50000,
            ])
            ->assertRedirect();

        $created = User::where('email', 'ada.branch@example.com')->firstOrFail();

        $this->assertSame([$this->lagos->id], $created->allowedStoreIds());
        $this->assertFalse($created->canAccessStore($this->ibadan->id));
    }

    public function test_clearing_the_branch_list_really_restores_full_access(): void
    {
        $user = $this->userWithStores('sales', [$this->lagos->id]);
        $user->forceFill(['store_id' => $this->lagos->id])->save();

        $this->assertSame([$this->lagos->id], $user->fresh()->allowedStoreIds());

        $user->syncAllowedStores([]);
        $user = $user->fresh();

        $this->assertNull($user->store_id, 'the legacy home branch must not re-lock the user');
        $this->assertNull($user->allowedStoreIds());
        $this->assertFalse($user->isStoreRestricted());
        $this->assertTrue($user->canAccessStore($this->ibadan->id));
    }

    public function test_a_restricted_user_may_switch_to_all_of_their_own_branches(): void
    {
        $user = $this->userWithStores('sales', [$this->lagos->id, $this->ibadan->id]);

        $this->actingAs($user)
            ->from(route('admin.dashboard'))
            ->post(route('admin.store.select'), ['store_id' => $this->lagos->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($this->lagos->id, session(StoreAccess::SESSION_KEY));

        $this->actingAs($user)
            ->from(route('admin.dashboard'))
            ->post(route('admin.store.select'), ['store_id' => ''])
            ->assertSessionHasNoErrors();

        $this->assertNull(session(StoreAccess::SESSION_KEY), 'clearing the branch must be allowed for a restricted user');
        $this->assertSame([$this->lagos->id, $this->ibadan->id], $user->fresh()->allowedStoreIds());
    }

    public function test_a_locked_product_cannot_be_opened_by_guessing_the_url(): void
    {
        $user = $this->userWithStores('sales', [$this->lagos->id]);
        $hidden = $this->productWithStore('Ibadan only panel', $this->ibadan);

        $this->actingAs($user)
            ->get(route('admin.products.show', $hidden))
            ->assertNotFound();
    }

    public function test_a_locked_product_cannot_be_edited_or_deleted_by_guessing_the_url(): void
    {
        $user = $this->userWithStores('sales', [$this->lagos->id]);
        $hidden = $this->productWithStore('Ibadan only panel', $this->ibadan);

        $this->actingAs($user)
            ->get(route('admin.products.edit', $hidden))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(route('admin.products.update', $hidden), [
                'name' => 'Hijacked',
                'cost_price' => 1,
                'selling_price' => 1,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('products', ['id' => $hidden->id, 'name' => 'Ibadan only panel']);
    }

    public function test_the_product_list_never_serialises_a_locked_branch(): void
    {
        $user = $this->userWithStores('sales', [$this->lagos->id]);
        $mine = $this->productWithStore('Lagos panel', $this->lagos);
        $this->productWithStore('Ibadan panel', $this->ibadan);

        $response = $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertOk();

        $payload = $response->viewData('page')['props'];

        $this->assertSame(
            [$this->lagos->id],
            collect($payload['products']['data'][0]['stores'])->pluck('id')->all(),
            'the serialised stores relation must contain only the granted branch'
        );
        $this->assertSame($mine->id, $payload['products']['data'][0]['id']);
        $this->assertStringNotContainsString('Ibadan panel', json_encode($payload['products']));
    }

    public function test_a_product_visible_elsewhere_cannot_be_adjusted_into_my_branch(): void
    {
        // Three branches: this login may reach Lagos and Abuja, never Ibadan.
        $abuja = Store::create(['name' => 'Test Abuja', 'code' => 'TABU', 'is_active' => true]);

        $user = $this->userWithStores('sales', [$this->lagos->id, $abuja->id]);
        $user->directPermissions()->attach(
            Permission::where('slug', 'inventory.adjust')->firstOrFail()->id
        );

        $mine = $this->productWithStore('Lagos panel', $this->lagos);
        $reachable = $this->productWithStore('Abuja panel', $abuja);
        $hidden = $this->productWithStore('Ibadan panel', $this->ibadan);

        // Visible in another reachable branch, but not stocked at the target.
        $this->actingAs($user)
            ->from(route('admin.stock.adjust'))
            ->post(route('admin.stock.adjust.submit'), [
                'product_id' => $reachable->id,
                'quantity' => 1,
                'type' => 'adjustment',
                'direction' => 'increase',
                'reason' => 'Moving a product from another branch into mine',
                'store_id' => $this->lagos->id,
            ])
            ->assertSessionHasErrors('product_id');

        $this->assertDatabaseMissing('product_store', [
            'product_id' => $reachable->id,
            'store_id' => $this->lagos->id,
        ]);

        // Not reachable at all: the id must not even be confirmed to exist.
        $this->actingAs($user)
            ->from(route('admin.stock.adjust'))
            ->post(route('admin.stock.adjust.submit'), [
                'product_id' => $hidden->id,
                'quantity' => 1,
                'type' => 'adjustment',
                'direction' => 'increase',
                'reason' => 'Reaching for a branch I cannot see',
                'store_id' => $this->lagos->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('product_store', [
            'product_id' => $hidden->id,
            'store_id' => $this->lagos->id,
        ]);

        // The product genuinely in my branch still works.
        $this->actingAs($user)
            ->from(route('admin.stock.adjust'))
            ->post(route('admin.stock.adjust.submit'), [
                'product_id' => $mine->id,
                'quantity' => 1,
                'type' => 'adjustment',
                'direction' => 'increase',
                'reason' => 'Legitimate correction in my own branch',
                'store_id' => $this->lagos->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('product_store', [
            'product_id' => $mine->id,
            'store_id' => $this->lagos->id,
            'current_quantity' => 21,
        ]);
    }

    public function test_a_tampered_bulk_id_list_cannot_reach_a_locked_product(): void
    {
        $user = $this->userWithStores('sales', [$this->lagos->id]);
        $user->directPermissions()->attach(
            Permission::where('slug', 'products.edit')->firstOrFail()->id
        );

        $this->productWithStore('Lagos panel', $this->lagos);
        $hidden = $this->productWithStore('Ibadan panel', $this->ibadan);

        $this->actingAs($user)
            ->from(route('admin.products.index'))
            ->post(route('admin.products.bulk-status'), [
                'ids' => [$hidden->id],
                'action' => 'deactivate',
            ])
            ->assertSessionHasErrors('ids.0');

        $this->assertSame('active', $hidden->fresh()->status);
    }

    public function test_attaching_an_existing_login_still_applies_the_chosen_branches(): void
    {
        $owner = $this->userWithStores('owner', []);
        $existing = User::factory()->create([
            'role_id' => Role::where('slug', 'sales')->firstOrFail()->id,
        ]);

        $salesRole = Role::where('slug', 'sales')->firstOrFail();
        $salesRole->forceFill([
            'access_config' => [
                'allowed_store_ids' => [$this->lagos->id],
            ],
        ])->save();

        $this->actingAs($owner)
            ->post(route('admin.staff.store'), [
                'name' => 'Existing Login',
                'email' => 'existing.login@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'role_id' => $salesRole->id,
                'base_salary' => 50000,
                'user_id' => $existing->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            [$this->lagos->id],
            $existing->fresh()->allowedStoreIds(),
            'branches must be applied when an existing login is attached to a staff record'
        );
    }

    public function test_the_last_purchase_summary_ignores_a_branch_i_cannot_reach(): void
    {
        $user = $this->userWithStores('sales', [$this->lagos->id]);
        $product = $this->productWithStore('Lagos panel', $this->lagos);

        // A permitted purchase, recorded first.
        $this->purchaseFor($product, $this->lagos, 'PO-MINE', 10);

        // A newer purchase in the locked branch, which must never be summarised
        // as my last purchase.
        $this->purchaseFor($product, $this->ibadan, 'PO-LOCKED', 5);

        $this->actingAs($user)
            ->get(route('admin.products.show', $product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.last_purchase.ref_id', 'PO-MINE')
                ->where('summary.total_purchased', 10)
            );
    }

    public function test_the_product_detail_page_hides_a_locked_branch_purchase_from_the_list(): void
    {
        $user = $this->userWithStores('sales', [$this->lagos->id]);
        $product = $this->productWithStore('Lagos panel', $this->lagos);

        $this->purchaseFor($product, $this->lagos, 'PO-MINE', 10);
        $this->purchaseFor($product, $this->ibadan, 'PO-LOCKED', 5);

        $response = $this->actingAs($user)->get(route('admin.products.show', $product));

        $this->assertStringNotContainsString('PO-LOCKED', $response->getContent());
    }

    private function purchaseFor(Product $product, Store $store, string $refId, int $quantity): void
    {
        $purchase = Purchase::create([
            'ref_id' => $refId,
            'purchase_date' => now()->toDateString(),
            'store_id' => $store->id,
            'status' => 'completed',
            'total' => $quantity * 100,
        ]);

        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_cost' => 100,
            'total' => $quantity * 100,
        ]);
    }

    private function productWithStore(string $name, Store $store): Product
    {
        $product = Product::create([
            'ref_id' => 'ACC-'.strtoupper($store->code).'-'.uniqid(),
            'sku' => 'ACC-'.strtoupper($store->code).'-'.uniqid(),
            'name' => $name,
            'cost_price' => 100,
            'selling_price' => 150,
        ]);

        $product->stores()->attach($store->id, [
            'current_quantity' => 20,
            'reorder_level' => 5,
            'average_cost' => 100,
            'selling_price' => 150,
        ]);

        return $product;
    }
}
