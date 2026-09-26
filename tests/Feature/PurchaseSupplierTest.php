<?php

namespace Tests\Feature;

use App\Models\Payment;
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

class PurchaseSupplierTest extends TestCase
{
    use RefreshDatabase;

    private int $productSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_supplier_created_from_the_admin_page_is_listed_and_offered_on_the_purchase_form(): void
    {
        $owner = $this->owner();
        $store = $this->store('PUR-SUP', 'Purchase Supplier Store');

        $this->actingAs($owner)
            ->post(route('admin.suppliers.store'), ['name' => 'Zephyr Solar Supplies'])
            ->assertRedirect();

        $supplier = Supplier::where('name', 'Zephyr Solar Supplies')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('admin.suppliers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Suppliers/Index')
                ->where('suppliers.total', 1)
                ->where('suppliers.data.0.id', $supplier->id)
                ->where('suppliers.data.0.name', 'Zephyr Solar Supplies')
            );

        $this->withSession(['admin_store_id' => $store->id])
            ->actingAs($owner)
            ->get(route('admin.purchases.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Purchases/Form')
                ->has('suppliers', 1)
                ->where('suppliers.0.id', $supplier->id)
            );
    }

    public function test_supplier_added_while_a_search_is_active_is_visible_after_saving(): void
    {
        $owner = $this->owner();
        $existing = Supplier::create(['ref_id' => 'SUP-SEED1', 'name' => 'Alpha Traders']);

        $this->actingAs($owner)
            ->from(route('admin.suppliers.index', ['search' => 'Alpha']))
            ->post(route('admin.suppliers.store'), ['name' => 'Beta Solar'])
            ->assertRedirect(route('admin.suppliers.index', ['search' => 'Beta Solar']));

        $created = Supplier::where('name', 'Beta Solar')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('admin.suppliers.index', ['search' => 'Beta Solar']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', 'Beta Solar')
                ->where('suppliers.total', 1)
                ->where('suppliers.data.0.id', $created->id)
            );

        $this->actingAs($owner)
            ->get(route('admin.suppliers.index', ['search' => 'Alpha']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('suppliers.total', 1)
                ->where('suppliers.data.0.id', $existing->id)
            );
    }

    public function test_quick_created_supplier_is_returned_for_inline_purchase_use(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->postJson(route('admin.suppliers.quick'), ['name' => 'Inline Supplier Ltd'])
            ->assertOk()
            ->assertJsonPath('supplier.name', 'Inline Supplier Ltd')
            ->assertJsonStructure(['supplier' => ['id', 'name']]);

        $this->assertDatabaseHas('suppliers', ['name' => 'Inline Supplier Ltd']);
    }

    public function test_quick_created_supplier_persists_the_full_contact_details(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->postJson(route('admin.suppliers.quick'), [
                'name' => 'Detailed Supplier Ltd',
                'contact_person' => 'Amaka Obi',
                'phone' => '08031234567',
                'email' => 'procurement@detailedsupplier.test',
                'address' => '14 Marina Way, Lagos',
            ])
            ->assertOk()
            ->assertJsonPath('supplier.name', 'Detailed Supplier Ltd')
            ->assertJsonPath('supplier.email', 'procurement@detailedsupplier.test');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Detailed Supplier Ltd',
            'contact_person' => 'Amaka Obi',
            'phone' => '08031234567',
            'email' => 'procurement@detailedsupplier.test',
            'address' => '14 Marina Way, Lagos',
        ]);
    }

    public function test_quick_created_supplier_rejects_an_invalid_email(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->postJson(route('admin.suppliers.quick'), [
                'name' => 'Bad Email Supplier',
                'email' => 'not-an-email',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseMissing('suppliers', ['name' => 'Bad Email Supplier']);
    }

    public function test_purchase_can_receive_stock_for_a_product_not_yet_stocked_in_the_selected_store(): void
    {
        $owner = $this->owner();
        $store = $this->store('PUR-NEW', 'Purchase New Product Store');
        $product = $this->product('Fresh Panel 450W');

        $this->withSession(['admin_store_id' => $store->id])
            ->actingAs($owner)
            ->get(route('admin.purchases.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Purchases/Form')
                ->has('products', 1)
                ->where('products.0.id', $product->id)
            );

        $this->withSession(['admin_store_id' => $store->id])
            ->actingAs($owner)
            ->post(route('admin.purchases.store'), [
                'purchase_date' => now()->toDateString(),
                'supplier_name' => 'Walk In Supplier',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 4, 'unit_cost' => 50000],
                ],
                'amount_paid' => 100000,
            ])
            ->assertRedirect();

        $this->assertSame(4, $product->fresh()->current_quantity);
        $this->assertDatabaseHas('product_store', [
            'product_id' => $product->id,
            'store_id' => $store->id,
            'current_quantity' => 4,
        ]);
    }

    public function test_repeated_lines_for_the_same_product_accumulate_into_one_inbound_quantity(): void
    {
        $owner = $this->owner();
        $store = $this->store('PUR-DUP', 'Purchase Duplicate Lines Store');
        $product = $this->product('Battery 100Ah');
        $supplier = Supplier::create(['ref_id' => 'SUP-DUP01', 'name' => 'Dup Supplier']);

        $this->withSession(['admin_store_id' => $store->id])
            ->actingAs($owner)
            ->post(route('admin.purchases.store'), [
                'purchase_date' => now()->toDateString(),
                'supplier_id' => $supplier->id,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 40000],
                    ['product_id' => $product->id, 'quantity' => 3, 'unit_cost' => 40000],
                ],
                'amount_paid' => 0,
            ])
            ->assertRedirect();

        $this->assertSame(8, $product->fresh()->current_quantity);
        $this->assertSame(
            8,
            (int) DB::table('product_store')
                ->where('product_id', $product->id)
                ->where('store_id', $store->id)
                ->value('current_quantity')
        );
    }

    public function test_voiding_a_purchase_restores_the_previous_weighted_average_cost(): void
    {
        $owner = $this->owner();
        $store = $this->store('PUR-VOID', 'Purchase Void Costing Store');
        $product = $this->product('Inverter 5KVA');
        $supplier = Supplier::create(['ref_id' => 'SUP-VOID1', 'name' => 'Void Supplier']);

        app(InventoryService::class)->setOpeningStock($product, 10, 80000, $owner->id, $store->id);

        $purchase = app(PurchaseService::class)->createPurchase(
            [
                'purchase_date' => now()->toDateString(),
                'supplier_id' => $supplier->id,
                'store_id' => $store->id,
                'amount_paid' => 0,
            ],
            [['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 100000]],
            $owner->id
        );

        $this->assertSame(20, $product->fresh()->current_quantity);
        $this->assertEqualsWithDelta(90000.0, (float) $product->fresh()->average_cost, 0.01);

        $this->withSession(['admin_store_id' => $store->id])
            ->actingAs($owner)
            ->from(route('admin.purchases.show', $purchase))
            ->post(route('admin.purchases.void', $purchase), ['void_reason' => 'Wrong invoice'])
            ->assertRedirect();

        $this->assertSame(10, $product->fresh()->current_quantity);
        $this->assertEqualsWithDelta(80000.0, (float) $product->fresh()->average_cost, 0.01);
        $this->assertEqualsWithDelta(
            80000.0,
            (float) DB::table('product_store')
                ->where('product_id', $product->id)
                ->where('store_id', $store->id)
                ->value('average_cost'),
            0.01
        );
        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'status' => 'void',
            'amount_paid' => 0,
            'balance' => 1000000,
        ]);
    }

    public function test_payment_on_a_voided_purchase_is_rejected(): void
    {
        $owner = $this->owner();
        $store = $this->store('PUR-VOIDPAY', 'Purchase Void Payment Store');
        $product = $this->product('Controller 60A');
        $supplier = Supplier::create(['ref_id' => 'SUP-VPAY1', 'name' => 'Void Pay Supplier']);

        $purchase = app(PurchaseService::class)->createPurchase(
            [
                'purchase_date' => now()->toDateString(),
                'supplier_id' => $supplier->id,
                'store_id' => $store->id,
                'amount_paid' => 0,
            ],
            [['product_id' => $product->id, 'quantity' => 2, 'unit_cost' => 30000]],
            $owner->id
        );

        app(PurchaseService::class)->voidPurchase($purchase, 'Duplicate entry', $owner->id);

        $this->withSession(['admin_store_id' => $store->id])
            ->actingAs($owner)
            ->from(route('admin.purchases.show', $purchase))
            ->post(route('admin.payments.store'), [
                'document_type' => 'purchase',
                'document_id' => $purchase->id,
                'amount' => 10000,
                'payment_method' => 'cash',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, Payment::where('document_type', 'purchase')->count());
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
            'ref_id' => 'PUR-PROD'.str_pad((string) ++$this->productSequence, 4, '0', STR_PAD_LEFT),
            'name' => $name,
            'sku' => 'PUR-'.str_pad((string) ++$this->productSequence, 4, '0', STR_PAD_LEFT),
            'unit' => 'piece',
            'cost_price' => 45000,
            'selling_price' => 70000,
        ]);
    }
}
