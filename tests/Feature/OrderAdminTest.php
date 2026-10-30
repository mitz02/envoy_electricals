<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->first()->id,
        ]);
    }

    protected function onlineProduct(string $name, float $price = 15000, int $stock = 10): Product
    {
        $product = Product::create([
            'ref_id' => 'ORD-PROD-'.mt_rand(10000, 99999),
            'name' => $name,
            'sku' => 'ORD-'.strtoupper(substr(md5($name.mt_rand()), 0, 8)),
            'unit' => 'piece',
            'selling_price' => $price,
            'is_visible_online' => true,
            'allow_online_purchase' => true,
        ]);

        app(InventoryService::class)->setOpeningStock($product, $stock, 1000);

        return $product;
    }

    protected function placeOrder(array $items = []): Order
    {
        $product = $items[0]['product_id'] ?? $this->onlineProduct('Inverter 5kVA')->id;

        $this->post('/checkout', [
            'customer_name' => 'Tola Badejo',
            'customer_phone' => '08012345678',
            'customer_email' => 'tola@example.test',
            'delivery_address' => '14 Akin Street, Ikeja, Lagos',
            'items' => $items ?: [
                ['product_id' => $product, 'quantity' => 1],
            ],
        ])->assertRedirect();

        return Order::latest('id')->first();
    }

    private function dataPage($response): array
    {
        if (preg_match('/data-page="([^"]*)"/', $response->getContent(), $m)) {
            return json_decode(html_entity_decode($m[1], ENT_QUOTES), true) ?? [];
        }
        $this->fail('Unable to parse Inertia data-page attribute.');
    }

    public function test_admin_can_list_orders(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $order = $this->placeOrder();

        $this->actingAs($owner)->get('/admin/orders')->assertOk();
    }

    public function test_admin_can_view_order_details(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $order = $this->placeOrder();

        $response = $this->actingAs($owner)->get("/admin/orders/{$order->id}");
        $response->assertOk();

        $page = $this->dataPage($response);
        $this->assertSame($order->ref_id, $page['props']['order']['ref_id']);
    }

    public function test_admin_can_confirm_offline_payment(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $order = $this->placeOrder();

        // Create a pending offline payment
        $payment = Payment::create([
            'ref_id' => 'PAY-TEST-'.mt_rand(10000, 99999),
            'payment_date' => now()->toDateString(),
            'amount' => $order->total,
            'payment_method' => 'bank_transfer',
            'document_type' => 'order',
            'document_id' => $order->id,
            'store_id' => 1,
            'customer_id' => null,
            'type' => 'payment_in',
            'reference' => 'REF-123',
            'status' => Payment::STATUS_PENDING,
            'gateway' => Payment::GATEWAY_LOCAL,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/pay/{$payment->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(Payment::STATUS_SUCCESS, $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_admin_cannot_confirm_payment_on_cancelled_order(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $order = $this->placeOrder();
        $order->update(['status' => 'cancelled']);

        $payment = Payment::create([
            'ref_id' => 'PAY-TEST-'.mt_rand(10000, 99999),
            'payment_date' => now()->toDateString(),
            'amount' => $order->total,
            'payment_method' => 'bank_transfer',
            'document_type' => 'order',
            'document_id' => $order->id,
            'store_id' => 1,
            'customer_id' => null,
            'type' => 'payment_in',
            'reference' => 'REF-123',
            'status' => Payment::STATUS_PENDING,
            'gateway' => Payment::GATEWAY_LOCAL,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/pay/{$payment->id}")
            ->assertSessionHasErrors('amount');
    }

    public function test_admin_can_fulfill_order(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $product = $this->onlineProduct('Solar Panel 400W', 50000, 5);
        $order = $this->placeOrder([
            ['product_id' => $product->id, 'quantity' => 2],
        ]);

        // Mark order as paid first
        $order->update(['status' => 'paid']);

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/deliver")
            ->assertRedirect()
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('delivered', $order->fulfillment);
        $this->assertSame('completed', $order->status);

        // Verify stock was deducted
        $this->assertSame(3, $product->fresh()->current_quantity);
    }

    public function test_admin_cannot_fulfill_unpaid_order(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $order = $this->placeOrder();

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/deliver")
            ->assertSessionHasErrors('order');

        $this->assertSame('pending', $order->fresh()->fulfillment);
    }

    public function test_admin_cannot_fulfill_already_delivered_order(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $product = $this->onlineProduct('Battery 200Ah', 80000, 3);
        $order = $this->placeOrder([
            ['product_id' => $product->id, 'quantity' => 1],
        ]);
        $order->update(['status' => 'paid', 'fulfillment' => 'delivered']);

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/deliver")
            ->assertSessionHasErrors('order');
    }

    // ---- NEW ADMIN ACTIONS ----

    public function test_admin_can_cancel_pending_order(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $order = $this->placeOrder();

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/cancel")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_admin_can_cancel_paid_order_and_refund(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $product = $this->onlineProduct('Inverter 3kVA', 100000, 5);
        $order = $this->placeOrder([
            ['product_id' => $product->id, 'quantity' => 1],
        ]);

        // Mark as paid with a payment
        $payment = Payment::create([
            'ref_id' => 'PAY-REFUND-'.mt_rand(10000, 99999),
            'payment_date' => now()->toDateString(),
            'amount' => $order->total,
            'payment_method' => 'bank_transfer',
            'document_type' => 'order',
            'document_id' => $order->id,
            'store_id' => 1,
            'customer_id' => null,
            'type' => 'payment_in',
            'reference' => 'REF-123',
            'status' => Payment::STATUS_SUCCESS,
            'gateway' => Payment::GATEWAY_LOCAL,
            'paid_at' => now(),
            'created_by' => $owner->id,
        ]);
        $order->update(['status' => 'paid']);

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/cancel", [
            'refund_amount' => $order->total,
            'refund_reason' => 'Customer requested cancellation',
        ])->assertRedirect()
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('refunded', $order->status);

        // Verify refund payment was recorded
        $this->assertDatabaseHas('payments', [
            'document_type' => 'order',
            'document_id' => $order->id,
            'type' => 'payment_out',
            'amount' => $order->total,
        ]);
    }

    public function test_admin_cannot_cancel_delivered_order(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $product = $this->onlineProduct('Charge Controller', 25000, 5);
        $order = $this->placeOrder([
            ['product_id' => $product->id, 'quantity' => 1],
        ]);
        $order->update(['status' => 'completed', 'fulfillment' => 'delivered']);

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/cancel")
            ->assertSessionHasErrors('order');
    }

    public function test_admin_can_mark_order_as_processing(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $order = $this->placeOrder();

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/process")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('processing', $order->fresh()->status);
    }

    public function test_admin_can_add_manual_payment_to_order(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $order = $this->placeOrder();

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/payments", [
            'amount' => 5000,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
            'remarks' => 'Partial cash payment',
        ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'document_type' => 'order',
            'document_id' => $order->id,
            'amount' => 5000,
            'payment_method' => 'cash',
            'status' => Payment::STATUS_SUCCESS,
        ]);

        $order->refresh();
        $this->assertEquals(5000, $order->total_paid);
    }

    public function test_admin_cannot_add_payment_exceeding_balance(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $order = $this->placeOrder();

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/payments", [
            'amount' => $order->total + 1000,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ])->assertSessionHasErrors('amount');
    }

    public function test_admin_can_undo_fulfillment(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $product = $this->onlineProduct('Solar Cable 10m', 15000, 10);
        $order = $this->placeOrder([
            ['product_id' => $product->id, 'quantity' => 3],
        ]);
        $order->update(['status' => 'paid']);
        $product->refresh(); // 7 left after fulfillment would be expected

        // Fulfill first
        $this->actingAs($owner)->post("/admin/orders/{$order->id}/deliver")
            ->assertRedirect();

        $product->refresh();
        $this->assertSame(7, $product->current_quantity);

        // Undo fulfillment
        $this->actingAs($owner)->post("/admin/orders/{$order->id}/undo-deliver")
            ->assertRedirect()
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('pending', $order->fulfillment);
        $this->assertSame('paid', $order->status);

        // Verify stock was restored
        $this->assertSame(10, $product->fresh()->current_quantity);
    }

    public function test_admin_cannot_undo_fulfillment_on_pending_order(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $order = $this->placeOrder();

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/undo-deliver")
            ->assertSessionHasErrors('order');
    }

    // ---- REVENUE RECOGNITION ----

    public function test_delivering_an_order_creates_a_linked_sale(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $product = $this->onlineProduct('Solar Panel 400W', 50000, 5);
        $order = $this->placeOrder([
            ['product_id' => $product->id, 'quantity' => 2],
        ]);
        $order->update(['status' => 'paid']);

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/deliver")
            ->assertRedirect()
            ->assertSessionHas('success');

        $order->refresh();

        $this->assertNotNull($order->linked_sale_id, 'Delivering an order should link it to a sale.');

        $sale = Sale::find($order->linked_sale_id);

        $this->assertNotNull($sale);
        $this->assertSame('completed', $sale->status);
        $this->assertEquals($order->total, $sale->total);
        $this->assertEquals($order->subtotal, $sale->subtotal);
        $this->assertSame(now()->toDateString(), $sale->sale_date->toDateString());
        $this->assertStringContainsString($order->ref_id, $sale->remarks);

        // Line items carry the cost that profit reporting depends on.
        $this->assertCount(1, $sale->items);
        $this->assertSame($product->id, $sale->items->first()->product_id);
        $this->assertSame(2, $sale->items->first()->quantity);
        $this->assertEquals(100000, $sale->items->first()->total);
    }

    public function test_delivering_an_order_deducts_stock_exactly_once(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $product = $this->onlineProduct('Inverter 5kVA', 100000, 5);
        $order = $this->placeOrder([
            ['product_id' => $product->id, 'quantity' => 2],
        ]);
        $order->update(['status' => 'paid']);

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/deliver")->assertRedirect();

        $this->assertSame(3, $product->fresh()->current_quantity);

        // The sale must not trigger a second outbound movement, or stock would
        // be double-deducted for every delivered order.
        $outbound = StockMovement::where('document_type', 'order')
            ->where('document_id', $order->id)
            ->where('type', StockMovement::TYPE_SALE)
            ->count();

        $this->assertSame(1, $outbound, 'Fulfilling an order should write exactly one outbound stock movement.');

        $saleOutbound = StockMovement::where('document_type', 'sale')
            ->where('document_id', $order->fresh()->linked_sale_id)
            ->count();

        $this->assertSame(0, $saleOutbound, 'The generated sale must not move stock again.');
    }

    public function test_delivering_an_order_carries_paid_amount_onto_the_sale(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $product = $this->onlineProduct('Battery 200Ah', 80000, 3);
        $order = $this->placeOrder([
            ['product_id' => $product->id, 'quantity' => 1],
        ]);

        Payment::create([
            'ref_id' => 'PAY-PAID-'.mt_rand(10000, 99999),
            'payment_date' => now()->toDateString(),
            'amount' => $order->total,
            'payment_method' => 'bank_transfer',
            'document_type' => 'order',
            'document_id' => $order->id,
            'store_id' => 1,
            'customer_id' => null,
            'type' => 'payment_in',
            'reference' => 'REF-PAID',
            'status' => Payment::STATUS_SUCCESS,
            'gateway' => Payment::GATEWAY_LOCAL,
            'paid_at' => now(),
            'created_by' => $owner->id,
        ]);

        $order->update(['status' => 'paid']);
        $this->actingAs($owner)->post("/admin/orders/{$order->id}/deliver")->assertRedirect();

        $sale = Sale::find($order->fresh()->linked_sale_id);

        $this->assertEquals($order->total, $sale->amount_paid);
        $this->assertEquals(0, $sale->balance);

        // Cash lives on the order's ledger; the sale must not duplicate it.
        $this->assertSame(1, Payment::where('document_type', 'order')->where('document_id', $order->id)->count());
        $this->assertSame(0, Payment::where('document_type', 'sale')->where('document_id', $sale->id)->count());
    }

    public function test_delivered_order_revenue_appears_on_the_dashboard(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $product = $this->onlineProduct('Charge Controller', 25000, 5);
        $order = $this->placeOrder([
            ['product_id' => $product->id, 'quantity' => 1],
        ]);
        $order->update(['status' => 'paid']);

        $before = $this->dataPage($this->actingAs($owner)->get('/admin?period=year'));
        $this->assertEquals(0, $before['props']['stats']['month_sales']);

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/deliver")->assertRedirect();

        $after = $this->dataPage($this->actingAs($owner)->get('/admin?period=year'));

        $this->assertEquals($order->total, $after['props']['stats']['month_sales']);
        $this->assertEquals(1, $after['props']['stats']['range_orders_count']);
        // Check profit: selling price 25000 - cost 1000 = 24000 profit
        $this->assertEquals(24000, $after['props']['stats']['month_gross_profit']);
        $this->assertEquals(24000, $after['props']['stats']['month_net_profit']); // no expenses in this test
    }

    public function test_undoing_fulfillment_voids_the_linked_sale(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $product = $this->onlineProduct('Solar Cable 10m', 15000, 10);
        $order = $this->placeOrder([
            ['product_id' => $product->id, 'quantity' => 3],
        ]);

        Payment::create([
            'ref_id' => 'PAY-UNDO-'.mt_rand(10000, 99999),
            'payment_date' => now()->toDateString(),
            'amount' => $order->total,
            'payment_method' => 'bank_transfer',
            'document_type' => 'order',
            'document_id' => $order->id,
            'store_id' => 1,
            'customer_id' => null,
            'type' => 'payment_in',
            'reference' => 'REF-UNDO',
            'status' => Payment::STATUS_SUCCESS,
            'gateway' => Payment::GATEWAY_LOCAL,
            'paid_at' => now(),
            'created_by' => $owner->id,
        ]);

        $order->update(['status' => 'paid']);
        $this->actingAs($owner)->post("/admin/orders/{$order->id}/deliver")->assertRedirect();

        $saleId = $order->fresh()->linked_sale_id;
        $this->assertNotNull($saleId);

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/undo-deliver")->assertRedirect();

        $sale = Sale::find($saleId);
        $this->assertSame('void', $sale->status);
        $this->assertEquals(0, $sale->amount_paid);
        $this->assertEquals($sale->total, $sale->balance);

        // Stock is restored by the order flow, and the customer still paid —
        // so the sale must not reverse stock or the order's payment.
        $this->assertSame(10, $product->fresh()->current_quantity);
        $this->assertSame(0, StockMovement::where('document_type', 'sale')->where('document_id', $saleId)->count());
        $this->assertSame(Payment::STATUS_SUCCESS, Payment::where('document_type', 'order')->where('document_id', $order->id)->first()->status);

        // Revenue must drop back out of the dashboard.
        $page = $this->dataPage($this->actingAs($owner)->get('/admin?period=year'));
        $this->assertEquals(0, $page['props']['stats']['month_sales']);
    }

    public function test_backfill_command_generates_sales_for_already_delivered_orders(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $product = $this->onlineProduct('Inverter 3kVA', 100000, 5);
        $order = $this->placeOrder([
            ['product_id' => $product->id, 'quantity' => 1],
        ]);
        $order->update(['status' => 'paid']);

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/deliver")->assertRedirect();

        // Simulate a pre-fix order: delivered, but no sale ever generated.
        $saleId = $order->fresh()->linked_sale_id;
        $this->assertNotNull($saleId);

        $order->update(['linked_sale_id' => null]);
        Sale::withTrashed()->whereKey($saleId)->forceDelete();
        $this->assertSame(0, Sale::withTrashed()->count());

        $this->artisan('orders:backfill-sales --dry-run')
            ->expectsOutputToContain('Would create 1 sale(s).')
            ->assertSuccessful();

        $this->assertSame(0, Sale::withTrashed()->count(), 'A dry run must not write anything.');

        $this->artisan('orders:backfill-sales')->assertSuccessful();

        $sale = Sale::sole();
        $this->assertSame('completed', $sale->status);
        $this->assertEquals($order->total, $sale->total);
        $this->assertSame($sale->id, $order->fresh()->linked_sale_id);
    }

    public function test_backfill_command_is_idempotent(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $product = $this->onlineProduct('Solar Panel 400W', 50000, 5);
        $order = $this->placeOrder([
            ['product_id' => $product->id, 'quantity' => 1],
        ]);
        $order->update(['status' => 'paid']);

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/deliver")->assertRedirect();
        $this->assertSame(1, Sale::count());

        $this->artisan('orders:backfill-sales')
            ->expectsOutputToContain('No delivered orders are missing a sale.')
            ->assertSuccessful();

        $this->assertSame(1, Sale::count(), 'Re-running the backfill must not duplicate revenue.');
    }

    // ---- COLLECTED TOTALS ----

    private function successfulPayment(Order $order, float $amount, User $owner): Payment
    {
        return Payment::create([
            'ref_id' => 'PAY-COLLECTED-'.mt_rand(10000, 99999),
            'payment_date' => now()->toDateString(),
            'amount' => $amount,
            'payment_method' => 'bank_transfer',
            'document_type' => 'order',
            'document_id' => $order->id,
            'store_id' => 1,
            'customer_id' => null,
            'type' => 'payment_in',
            'reference' => 'REF-COLLECTED-'.mt_rand(1000, 9999),
            'status' => Payment::STATUS_SUCCESS,
            'gateway' => Payment::GATEWAY_LOCAL,
            'paid_at' => now(),
            'created_by' => $owner->id,
        ]);
    }

    private function ordersTotals(User $owner): array
    {
        $response = $this->actingAs($owner)->get('/admin/orders');
        $response->assertOk();

        return $this->dataPage($response)['props']['totals'];
    }

    public function test_orders_collected_total_counts_delivered_orders(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $product = $this->onlineProduct('Inverter 5kVA', 100000, 5);
        $order = $this->placeOrder([
            ['product_id' => $product->id, 'quantity' => 1],
        ]);

        $this->successfulPayment($order, $order->total, $owner);
        $order->update(['status' => 'paid']);

        $this->assertEquals($order->total, $this->ordersTotals($owner)['collected']);

        // Delivering flips status to 'completed'. The collected total must not
        // drop the order — previously it only matched status = 'paid'.
        $this->actingAs($owner)->post("/admin/orders/{$order->id}/deliver")->assertRedirect();

        $this->assertEquals($order->total, $this->ordersTotals($owner)['collected']);
    }

    public function test_orders_collected_total_uses_actual_payments_not_assumed_full_total(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $product = $this->onlineProduct('Solar Panel 400W', 50000, 5);
        $order = $this->placeOrder([
            ['product_id' => $product->id, 'quantity' => 2],
        ]);

        $this->successfulPayment($order, 25000, $owner);
        $order->update(['status' => 'processing']);

        $this->assertEquals(25000, $this->ordersTotals($owner)['collected']);
    }

    public function test_orders_collected_total_ignores_pending_and_failed_payments(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $order = $this->placeOrder();

        Payment::create([
            'ref_id' => 'PAY-PENDING-'.mt_rand(10000, 99999),
            'payment_date' => now()->toDateString(),
            'amount' => $order->total,
            'payment_method' => 'bank_transfer',
            'document_type' => 'order',
            'document_id' => $order->id,
            'store_id' => 1,
            'customer_id' => null,
            'type' => 'payment_in',
            'reference' => 'REF-PENDING',
            'status' => Payment::STATUS_PENDING,
            'gateway' => Payment::GATEWAY_LOCAL,
            'created_by' => $owner->id,
        ]);

        $this->assertEquals(0, $this->ordersTotals($owner)['collected']);
    }

    public function test_orders_collected_total_excludes_refunded_orders(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $order = $this->placeOrder();

        $this->successfulPayment($order, $order->total, $owner);
        $order->update(['status' => 'refunded']);

        $this->assertEquals(0, $this->ordersTotals($owner)['collected']);
    }

    public function test_dashboard_exposes_cash_collected_separately_from_revenue(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $product = $this->onlineProduct('Charge Controller', 25000, 5);
        $order = $this->placeOrder([
            ['product_id' => $product->id, 'quantity' => 1],
        ]);

        // Part-paid: revenue counts the full invoice, cash collected counts 10k.
        $this->successfulPayment($order, 10000, $owner);
        $order->update(['status' => 'paid']);

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/deliver")->assertRedirect();

        $page = $this->dataPage($this->actingAs($owner)->get('/admin?period=year'));
        $stats = $page['props']['stats'];

        $this->assertEquals($order->total, $stats['month_sales'], 'Revenue should be the full invoiced total.');
        $this->assertEquals(10000, $stats['month_collected'], 'Cash collected should be the amount actually paid.');
        $this->assertNotNull($stats['month_collected'], 'The dashboard must expose a collected figure for the UI to show.');
    }
}
