<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\PurchaseService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductHubTest extends TestCase
{
    use RefreshDatabase;

    private int $productSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_legacy_receive_stock_url_redirects_to_the_purchase_form(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->get('/admin/stock/receive')
            ->assertRedirect(route('admin.purchases.create'));
    }

    public function test_receiving_stock_is_no_longer_reachable_as_a_post_endpoint(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->post('/admin/stock/receive', [])
            ->assertStatus(405);
    }

    public function test_bulk_receive_action_from_products_opens_a_purchase_with_those_products_preselected(): void
    {
        $owner = $this->owner();
        $store = $this->store('HUB-PRESELECT', 'Hub Preselect Store');
        $first = $this->product('Panel Alpha');
        $second = $this->product('Panel Beta');
        $this->product('Panel Excluded');

        $this->withSession(['admin_store_id' => $store->id])
            ->actingAs($owner)
            ->get(route('admin.purchases.create', ['products' => "{$first->id},{$second->id}"]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Purchases/Form')
                ->where('preselected_product_ids', [$first->id, $second->id])
            );
    }

    public function test_preselected_product_ids_ignore_unknown_and_duplicate_entries(): void
    {
        $owner = $this->owner();
        $store = $this->store('HUB-PRESELECT-DUP', 'Hub Preselect Dup Store');
        $product = $this->product('Panel Only');

        $this->withSession(['admin_store_id' => $store->id])
            ->actingAs($owner)
            ->get(route('admin.purchases.create', ['products' => "{$product->id},{$product->id},0,-4,99999"]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('preselected_product_ids', [$product->id])
            );
    }

    public function test_product_detail_page_reports_position_costs_and_purchase_history(): void
    {
        $owner = $this->owner();
        $store = $this->store('HUB-DETAIL', 'Hub Detail Store');
        $supplier = Supplier::create(['ref_id' => 'SUP-HUB1', 'name' => 'Hub Supplier']);
        $product = $this->product('Inverter Prime');

        $purchase = app(PurchaseService::class)->createPurchase(
            [
                'purchase_date' => now()->toDateString(),
                'supplier_id' => $supplier->id,
                'store_id' => $store->id,
                'amount_paid' => 0,
            ],
            [['product_id' => $product->id, 'quantity' => 4, 'unit_cost' => 50000]],
            $owner->id
        );

        $this->withSession(['admin_store_id' => $store->id])
            ->actingAs($owner)
            ->get(route('admin.products.show', $product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Products/Show')
                ->where('product.id', $product->id)
                ->where('summary.on_hand', 4)
                ->where('summary.average_cost', 50000)
                ->where('summary.stock_value', 200000)
                ->where('summary.total_purchased', 4)
                ->where('summary.total_sold', 0)
                ->where('summary.last_purchase.ref_id', $purchase->ref_id)
                ->where('stores.0.store_id', $store->id)
                ->where('stores.0.current_quantity', 4)
                ->where('stores.0.stock_value', 200000)
                ->where('purchases.total', 1)
                ->where('purchases.data.0.purchase.ref_id', $purchase->ref_id)
                ->where('purchases.data.0.quantity', 4)
                ->where('purchases.data.0.unit_cost', 50000)
                ->where('movements.total', 1)
                ->where('movements.data.0.type', 'purchase')
                ->where('movements.data.0.quantity_change', 4)
                ->where('movements.data.0.new_quantity', 4)
            );
    }

    public function test_product_detail_honours_the_selected_store_rather_than_the_global_totals(): void
    {
        $owner = $this->owner();
        $first = $this->store('HUB-STORE-A', 'Hub Store A');
        $second = $this->store('HUB-STORE-B', 'Hub Store B');
        $product = $this->product('Battery Cell');

        app(InventoryService::class)->inbound(
            $product,
            10,
            'adjustment',
            'HUB-OPENING-A',
            20000,
            storeId: $first->id
        );

        app(InventoryService::class)->inbound(
            $product,
            3,
            'adjustment',
            'HUB-OPENING-B',
            20000,
            storeId: $second->id
        );

        $this->assertSame(13, (int) $product->fresh()->current_quantity);

        $this->withSession(['admin_store_id' => $second->id])
            ->actingAs($owner)
            ->get(route('admin.products.show', $product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedStoreId', $second->id)
                ->where('summary.on_hand', 3)
                ->where('summary.stock_value', 60000)
                ->has('stores', 2)
                ->where('stores.0.store_id', $first->id)
                ->where('stores.0.current_quantity', 10)
                ->where('stores.1.store_id', $second->id)
                ->where('stores.1.current_quantity', 3)
            );
    }

    public function test_bulk_stock_adjustment_preserves_average_cost_and_records_a_ledger_entry(): void
    {
        $owner = $this->owner();
        $store = $this->store('HUB-ADJUST', 'Hub Adjust Store');
        $product = $this->product('Controller Adjustable');

        app(InventoryService::class)->inbound($product, 10, 'purchase', 'HUB-SEED', 30000, storeId: $store->id);

        $this->actingAs($owner)
            ->from(route('admin.products.index'))
            ->post(route('admin.products.bulk-adjust-stock'), [
                'ids' => [$product->id],
                'store_id' => $store->id,
                'type' => 'adjustment',
                'quantity_change' => 5,
                'reason' => 'Recount correction',
            ])
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();

        $this->assertSame(15, (int) $product->current_quantity);
        $this->assertSame(30000.0, (float) $product->average_cost, 'adjustment must not change average cost');

        $movement = DB::table('stock_movements')
            ->where('product_id', $product->id)
            ->where('document_type', 'bulk_adjustment')
            ->first();

        $this->assertNotNull($movement);
        $this->assertSame(5, (int) $movement->quantity_change);
        $this->assertSame(15, (int) $movement->new_quantity);
    }

    public function test_bulk_stock_adjustment_rejects_a_zero_change(): void
    {
        $owner = $this->owner();
        $store = $this->store('HUB-ADJUST-ZERO', 'Hub Adjust Zero Store');
        $product = $this->product('Controller NoOp');

        $this->actingAs($owner)
            ->post(route('admin.products.bulk-adjust-stock'), [
                'ids' => [$product->id],
                'store_id' => $store->id,
                'type' => 'adjustment',
                'quantity_change' => 0,
                'reason' => 'No change',
            ])
            ->assertSessionHasErrors('quantity_change');
    }

    public function test_negative_bulk_stock_adjustment_is_recorded_as_an_outbound_movement(): void
    {
        $owner = $this->owner();
        $store = $this->store('HUB-ADJUST-NEG', 'Hub Adjust Negative Store');
        $product = $this->product('Controller Shrinking');

        app(InventoryService::class)->inbound($product, 8, 'purchase', 'HUB-SEED-NEG', 15000, storeId: $store->id);

        $this->actingAs($owner)
            ->from(route('admin.products.index'))
            ->post(route('admin.products.bulk-adjust-stock'), [
                'ids' => [$product->id],
                'store_id' => $store->id,
                'type' => 'damage',
                'quantity_change' => -3,
                'reason' => 'Broken in transit',
            ])
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();

        $this->assertSame(5, (int) $product->current_quantity);
        $this->assertSame(15000.0, (float) $product->average_cost);

        $this->assertSame(
            -3,
            (int) DB::table('stock_movements')
                ->where('product_id', $product->id)
                ->where('type', 'damage')
                ->value('quantity_change')
        );
    }

    private function owner(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->firstOrFail()->id,
        ]);
    }

    private function store(string $code, string $name): Store
    {
        return Store::create([
            'name' => $name,
            'code' => $code,
            'is_active' => true,
            'is_default' => false,
        ]);
    }

    private function product(string $name): Product
    {
        return Product::create([
            'ref_id' => 'HUB-PROD'.str_pad((string) ++$this->productSequence, 4, '0', STR_PAD_LEFT),
            'name' => $name,
            'sku' => 'HUB-'.str_pad((string) ++$this->productSequence, 4, '0', STR_PAD_LEFT),
            'unit' => 'piece',
            'cost_price' => 45000,
            'selling_price' => 70000,
        ]);
    }
}
