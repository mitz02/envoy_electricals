<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use App\Services\PurchaseService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StockPageTest extends TestCase
{
    use RefreshDatabase;

    private Store $lagos;

    private Store $ibadan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->lagos = Store::create(['name' => 'Test Lagos', 'code' => 'TLAG', 'is_active' => true, 'is_default' => true]);
        $this->ibadan = Store::create(['name' => 'Test Ibadan', 'code' => 'TIBA', 'is_active' => true, 'is_default' => false]);
    }

    private function userWithPermission(string $slug): User
    {
        $user = User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->firstOrFail()->id,
        ]);

        $user->directPermissions()->attach(
            Permission::where('slug', $slug)->firstOrFail()->id
        );

        return $user;
    }

    private function productWithStock(Store $store, int $quantity = 20, float $averageCost = 100): Product
    {
        $product = Product::create([
            'ref_id' => 'STK-'.$store->code,
            'sku' => 'STK-'.$store->code,
            'name' => 'Panel '.$store->code,
            'cost_price' => 100,
            'selling_price' => 150,
            'current_quantity' => $quantity,
            'average_cost' => $averageCost,
        ]);

        $product->stores()->attach($store->id, [
            'current_quantity' => $quantity,
            'reorder_level' => 5,
            'average_cost' => $averageCost,
            'selling_price' => 150,
        ]);

        return $product;
    }

    public function test_a_correction_records_the_branch_count_not_the_global_total(): void
    {
        $user = $this->userWithPermission('inventory.adjust');

        // 100 in Lagos, 100 in Ibadan: the global total is 200.
        $product = Product::create([
            'ref_id' => 'STK-MULTI',
            'sku' => 'STK-MULTI',
            'name' => 'Multi branch panel',
            'cost_price' => 100,
            'selling_price' => 150,
            'current_quantity' => 200,
        ]);

        $product->stores()->attach([
            $this->lagos->id => ['current_quantity' => 100, 'average_cost' => 100, 'reorder_level' => 5],
            $this->ibadan->id => ['current_quantity' => 100, 'average_cost' => 100, 'reorder_level' => 5],
        ]);

        $this->actingAs($user)
            ->post(route('admin.stock.adjust.submit'), [
                'product_id' => $product->id,
                'quantity' => 5,
                'type' => 'adjustment',
                'direction' => 'decrease',
                'reason' => 'Lagos shelf count was wrong',
                'store_id' => $this->lagos->id,
            ])
            ->assertSessionHasNoErrors();

        $movement = DB::table('stock_movements')->where('product_id', $product->id)->first();

        $this->assertSame(100, (int) $movement->prev_quantity, 'ledger must record the branch count before the correction');
        $this->assertSame(95, (int) $movement->new_quantity, 'ledger must record the branch count after the correction');
        $this->assertSame(95, (int) $product->stores()->firstWhere('stores.id', $this->lagos->id)->pivot->current_quantity);
        $this->assertSame(100, (int) $product->stores()->firstWhere('stores.id', $this->ibadan->id)->pivot->current_quantity, 'the other branch must be untouched');
    }

    public function test_voiding_a_purchase_restores_the_previous_average_cost(): void
    {
        $user = $this->userWithPermission('purchases.void');
        $product = $this->productWithStock($this->lagos, 20, 4000);

        $purchase = app(PurchaseService::class)->createPurchase([
            'store_id' => $this->lagos->id,
            'supplier_name' => 'Void Test Supplier',
            'purchase_date' => now()->toDateString(),
        ], [
            ['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 5000],
        ], $user->id);

        $product->refresh();
        $this->assertSame(30, (int) $product->current_quantity);
        $this->assertSame(4333.33, (float) $product->average_cost, 'purchase blends into the weighted average');

        app(PurchaseService::class)->voidPurchase($purchase, 'Supplier sent the wrong item', $user->id);

        $product->refresh();
        $this->assertSame(20, (int) $product->current_quantity);
        $this->assertSame(4000.0, (float) $product->average_cost, 'voiding must restore the pre-purchase average cost');
    }

    public function test_voiding_the_first_purchase_on_an_empty_product_leaves_the_zero_cost_alone(): void
    {
        $user = $this->userWithPermission('purchases.void');
        $product = $this->productWithStock($this->lagos, 0, 0);

        $purchase = app(PurchaseService::class)->createPurchase([
            'store_id' => $this->lagos->id,
            'supplier_name' => 'First Purchase Supplier',
            'purchase_date' => now()->toDateString(),
        ], [
            ['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 5000],
        ], $user->id);

        app(PurchaseService::class)->voidPurchase($purchase, 'Cancelled before delivery', $user->id);

        $product->refresh();
        $this->assertSame(0, (int) $product->current_quantity, 'voiding must still remove the received quantity');
    }

    public function test_voiding_a_purchase_in_one_branch_keeps_the_other_branch_intact(): void
    {
        $user = $this->userWithPermission('purchases.void');

        $product = Product::create([
            'ref_id' => 'STK-VOID-MULTI',
            'sku' => 'STK-VOID-MULTI',
            'name' => 'Void multi panel',
            'cost_price' => 7000,
            'selling_price' => 9000,
            'current_quantity' => 100,
            'average_cost' => 7000,
        ]);

        $product->stores()->attach([
            $this->lagos->id => ['current_quantity' => 0, 'average_cost' => 0, 'reorder_level' => 5],
            $this->ibadan->id => ['current_quantity' => 100, 'average_cost' => 7000, 'reorder_level' => 5],
        ]);

        $purchase = app(PurchaseService::class)->createPurchase([
            'store_id' => $this->lagos->id,
            'supplier_name' => 'Void Multi Supplier',
            'purchase_date' => now()->toDateString(),
        ], [
            ['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 5000],
        ], $user->id);

        $product->refresh();
        $this->assertSame(110, (int) $product->current_quantity);
        $this->assertSame(6818.18, (float) $product->average_cost, 'purchase blends into the weighted average');

        app(PurchaseService::class)->voidPurchase($purchase, 'Wrong item delivered', $user->id);

        $product->refresh();
        $this->assertSame(100, (int) $product->current_quantity, 'global total must return to its pre-purchase value');
        $this->assertSame(0, (int) $product->stores()->firstWhere('stores.id', $this->lagos->id)->pivot->current_quantity);
        $this->assertSame(100, (int) $product->stores()->firstWhere('stores.id', $this->ibadan->id)->pivot->current_quantity, 'the untouched branch must keep its stock');
        $this->assertSame(7000.0, (float) $product->average_cost, 'average cost must fall back to the remaining branch cost');
    }

    public function test_the_movements_page_explains_totals_and_movement_kinds(): void
    {
        $user = $this->userWithPermission('inventory.view');
        $product = $this->productWithStock($this->lagos);

        StockMovement::create([
            'ref_id' => 'MOV-1',
            'product_id' => $product->id,
            'store_id' => $this->lagos->id,
            'type' => StockMovement::TYPE_PURCHASE,
            'quantity_change' => 10,
            'prev_quantity' => 0,
            'new_quantity' => 10,
            'unit_cost' => 100,
            'reference' => 'PO-1',
            'movement_date' => now(),
            'user_id' => $user->id,
        ]);

        StockMovement::create([
            'ref_id' => 'MOV-2',
            'product_id' => $product->id,
            'store_id' => $this->lagos->id,
            'type' => StockMovement::TYPE_SALE,
            'quantity_change' => -4,
            'prev_quantity' => 10,
            'new_quantity' => 6,
            'unit_cost' => 100,
            'reference' => 'INV-1',
            'movement_date' => now(),
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.stock.movements'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Inventory/Movements')
                ->where('summary.entries', 2)
                ->where('summary.units_in', 10)
                ->where('summary.units_out', 4)
                ->where('summary.net', 6)
                ->where('activeFilterCount', 0)
                ->has('types', 8)
                ->where('types.0.value', StockMovement::TYPE_OPENING)
                ->where('types.0.label', 'Opening balance')
                ->where('types.0.description', 'Stock you counted and loaded when the system was set up.')
                ->where('types.2.value', StockMovement::TYPE_SALE)
                ->where('types.2.units', -4)
            );
    }

    public function test_the_movements_page_hides_another_branchs_history(): void
    {
        $user = $this->userWithPermission('inventory.view');
        session(['admin_store_id' => $this->lagos->id]);

        $hidden = $this->productWithStock($this->ibadan);

        StockMovement::create([
            'ref_id' => 'MOV-3',
            'product_id' => $hidden->id,
            'store_id' => $this->ibadan->id,
            'type' => StockMovement::TYPE_PURCHASE,
            'quantity_change' => 50,
            'prev_quantity' => 0,
            'new_quantity' => 50,
            'unit_cost' => 100,
            'reference' => 'HIDDEN-1',
            'movement_date' => now(),
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.stock.movements'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.entries', 0)
                ->where('summary.units_in', 0)
            );
    }

    public function test_the_adjust_page_targets_one_explicit_branch_with_real_options(): void
    {
        $user = $this->userWithPermission('inventory.adjust');
        $this->productWithStock($this->lagos, 20);
        $this->productWithStock($this->ibadan, 3);

        $this->actingAs($user)
            ->get(route('admin.stock.adjust', ['store_id' => $this->lagos->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Inventory/Adjust')
                ->where('activeStoreId', $this->lagos->id)
                ->has('stores', 3)
                ->has('products', 1)
                ->where('products.0.name', 'Panel TLAG')
                ->where('products.0.current_quantity', 20)
            );
    }

    public function test_the_adjust_page_always_resolves_to_one_branch_even_without_a_query(): void
    {
        $user = $this->userWithPermission('inventory.adjust');
        $default = Store::where('is_default', true)->firstOrFail();

        $this->productWithStock($default, 7);

        $this->actingAs($user)
            ->get(route('admin.stock.adjust'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('activeStoreId', $default->id)
                ->has('products', 1)
                ->where('products.0.name', 'Panel '.$default->code)
                ->where('products.0.current_quantity', 7)
            );
    }

    public function test_switching_branch_reloads_quantities_for_that_branch(): void
    {
        $user = $this->userWithPermission('inventory.adjust');
        $this->productWithStock($this->lagos, 20);
        $this->productWithStock($this->ibadan, 3);

        $this->actingAs($user)
            ->get(route('admin.stock.adjust', ['store_id' => $this->ibadan->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('activeStoreId', $this->ibadan->id)
                ->has('products', 1)
                ->where('products.0.name', 'Panel TIBA')
                ->where('products.0.current_quantity', 3)
            );
    }

    public function test_the_adjust_pages_history_follows_the_branch_in_the_query_not_the_session(): void
    {
        $user = $this->userWithPermission('inventory.adjust');

        // The session selection points at Lagos...
        session(['admin_store_id' => $this->lagos->id]);

        $lagosProduct = $this->productWithStock($this->lagos, 20);
        $this->productWithStock($this->ibadan, 3);

        StockMovement::create([
            'ref_id' => 'MOV-LAGOS',
            'product_id' => $lagosProduct->id,
            'store_id' => $this->lagos->id,
            'type' => StockMovement::TYPE_ADJUSTMENT,
            'quantity_change' => 1,
            'prev_quantity' => 20,
            'new_quantity' => 21,
            'reason' => 'Lagos recount',
            'movement_date' => now(),
            'user_id' => $user->id,
        ]);

        StockMovement::create([
            'ref_id' => 'MOV-IBADAN',
            'product_id' => $lagosProduct->id,
            'store_id' => $this->ibadan->id,
            'type' => StockMovement::TYPE_ADJUSTMENT,
            'quantity_change' => 2,
            'prev_quantity' => 3,
            'new_quantity' => 5,
            'reason' => 'Ibadan recount',
            'movement_date' => now(),
            'user_id' => $user->id,
        ]);

        // ...but the page is asked for Ibadan, so the history shown beside the
        // Ibadan quantities must be the Ibadan one, not the session's Lagos one.
        $this->actingAs($user)
            ->get(route('admin.stock.adjust', ['store_id' => $this->ibadan->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Inventory/Adjust')
                ->where('activeStoreId', $this->ibadan->id)
                ->where('movements.data', fn ($rows) => collect($rows)->pluck('reason')->all() === ['Ibadan recount'])
            );
    }

    public function test_a_correction_can_decrease_stock(): void
    {
        $user = $this->userWithPermission('inventory.adjust');
        $product = $this->productWithStock($this->lagos, 20);

        $this->actingAs($user)
            ->from(route('admin.stock.adjust'))
            ->post(route('admin.stock.adjust.submit'), [
                'product_id' => $product->id,
                'quantity' => 5,
                'type' => 'adjustment',
                'direction' => 'decrease',
                'reason' => 'Counted the shelf and found 5 missing',
                'store_id' => $this->lagos->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(15, (int) $product->stores()->firstWhere('stores.id', $this->lagos->id)->pivot->current_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_ADJUSTMENT,
            'quantity_change' => -5,
            'prev_quantity' => 20,
            'new_quantity' => 15,
        ]);
    }

    public function test_a_correction_can_increase_stock(): void
    {
        $user = $this->userWithPermission('inventory.adjust');
        $product = $this->productWithStock($this->lagos, 20);

        $this->actingAs($user)
            ->from(route('admin.stock.adjust'))
            ->post(route('admin.stock.adjust.submit'), [
                'product_id' => $product->id,
                'quantity' => 3,
                'type' => 'adjustment',
                'direction' => 'increase',
                'reason' => 'Found 3 units behind the rack',
                'store_id' => $this->lagos->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(23, (int) $product->stores()->firstWhere('stores.id', $this->lagos->id)->pivot->current_quantity);
    }

    public function test_damage_always_removes_stock_regardless_of_direction(): void
    {
        $user = $this->userWithPermission('inventory.adjust');
        $product = $this->productWithStock($this->lagos, 20);

        $this->actingAs($user)
            ->from(route('admin.stock.adjust'))
            ->post(route('admin.stock.adjust.submit'), [
                'product_id' => $product->id,
                'quantity' => 2,
                'type' => 'damage',
                'direction' => 'increase',
                'reason' => 'Cracked in storage',
                'store_id' => $this->lagos->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(18, (int) $product->stores()->firstWhere('stores.id', $this->lagos->id)->pivot->current_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_DAMAGE,
            'quantity_change' => -2,
        ]);
    }

    public function test_a_return_always_adds_stock(): void
    {
        $user = $this->userWithPermission('inventory.adjust');
        $product = $this->productWithStock($this->lagos, 20);

        $this->actingAs($user)
            ->from(route('admin.stock.adjust'))
            ->post(route('admin.stock.adjust.submit'), [
                'product_id' => $product->id,
                'quantity' => 4,
                'type' => 'return',
                'direction' => 'decrease',
                'reason' => 'Customer brought it back',
                'store_id' => $this->lagos->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(24, (int) $product->stores()->firstWhere('stores.id', $this->lagos->id)->pivot->current_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_RETURN,
            'quantity_change' => 4,
        ]);
    }

    public function test_an_adjustment_must_name_a_branch(): void
    {
        $user = $this->userWithPermission('inventory.adjust');
        $product = $this->productWithStock($this->lagos, 20);

        $this->actingAs($user)
            ->from(route('admin.stock.adjust'))
            ->post(route('admin.stock.adjust.submit'), [
                'product_id' => $product->id,
                'quantity' => 1,
                'type' => 'adjustment',
                'direction' => 'increase',
                'reason' => 'Test',
            ])
            ->assertSessionHasErrors('store_id');
    }

    public function test_an_adjustment_must_explain_itself(): void
    {
        $user = $this->userWithPermission('inventory.adjust');
        $product = $this->productWithStock($this->lagos, 20);

        $this->actingAs($user)
            ->from(route('admin.stock.adjust'))
            ->post(route('admin.stock.adjust.submit'), [
                'product_id' => $product->id,
                'quantity' => 1,
                'type' => 'adjustment',
                'direction' => 'increase',
                'store_id' => $this->lagos->id,
            ])
            ->assertSessionHasErrors('reason');
    }
}
