<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\PaymentService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
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
            'ref_id' => 'CK-PROD-'.mt_rand(10000, 99999),
            'name' => $name,
            'sku' => 'CK-'.strtoupper(substr(md5($name.mt_rand()), 0, 8)),
            'unit' => 'piece',
            'selling_price' => $price,
            'is_visible_online' => true,
            'allow_online_purchase' => true,
        ]);

        app(InventoryService::class)->setOpeningStock($product, $stock, 1000);

        return $product;
    }

    protected function placeOrder(): Order
    {
        $product = $this->onlineProduct('Inverter 5kVA');

        $this->post('/checkout', [
            'customer_name' => 'Tola Badejo',
            'customer_phone' => '08012345678',
            'customer_email' => 'tola@example.test',
            'delivery_address' => '14 Akin Street, Ikeja, Lagos',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        return Order::latest('id')->first();
    }

    private function dataPage(TestResponse $response): array
    {
        if (preg_match('/data-page="([^"]*)"/', $response->getContent(), $m)) {
            return json_decode(html_entity_decode($m[1], ENT_QUOTES), true) ?? [];
        }

        $this->fail('Unable to parse Inertia data-page attribute. Status: '.$response->getStatusCode().' Exception: '.($response->exception ? $response->exception->getMessage() : 'none'));
    }

    public function test_checkout_creates_order_with_server_prices(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $product = $this->onlineProduct('Inverter 5kVA', 200000);

        $this->post('/checkout', [
            'customer_name' => 'Tola Badejo',
            'customer_phone' => '08012345678',
            'customer_email' => 'tola@example.test',
            'delivery_address' => '14 Akin Street, Ikeja, Lagos',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Tola Badejo',
            'subtotal' => 400000,
            'tax' => 0,
            'total' => 400000,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 200000,
            'total' => 400000,
        ]);
    }

    public function test_guest_can_checkout_without_an_account(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $product = $this->onlineProduct('Inverter 5kVA', 15000);

        $this->post('/checkout', [
            'customer_name' => 'Guest Shopper',
            'customer_phone' => '08012345678',
            'delivery_address' => '14 Akin Street, Ikeja, Lagos',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $order = Order::latest('id')->first();
        $this->assertNotNull($order);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => null,
            'customer_email' => null,
            'status' => 'pending',
        ]);

        $this->get('/orders/'.$order->ref_id.'/pay')->assertOk();
    }

    public function test_checkout_rejects_insufficient_stock(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $product = $this->onlineProduct('Small Inverter', 50000, 1);

        $this->post('/checkout', [
            'customer_name' => 'Tola Badejo',
            'customer_phone' => '08012345678',
            'delivery_address' => 'Lagos',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ])->assertSessionHasErrors('items');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_pay_page_shows_order_balance(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $order = $this->placeOrder();

        $response = $this->get('/orders/'.$order->ref_id.'/pay');
        $response->assertOk();

        $page = $this->dataPage($response);
        $this->assertSame($order->ref_id, $page['props']['order']['ref_id']);
        $this->assertSame(15000.0, (float) $page['props']['balance']);
    }

    public function test_paystack_link_creates_pending_order_payment(): void
    {
        config(['services.paystack.secret_key' => 'sk_test_paystack']);
        Http::fake([
            'api.paystack.co/*' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/order123', 'reference' => 'PAY-1'],
            ]),
        ]);

        $order = $this->placeOrder();

        $response = $this->postJson('/orders/'.$order->ref_id.'/paystack');
        $response->assertOk()->assertJsonPath('authorization_url', 'https://checkout.paystack.com/order123');

        $this->assertDatabaseHas('payments', [
            'document_type' => 'order',
            'document_id' => $order->id,
            'amount' => $order->total,
            'gateway' => Payment::GATEWAY_PAYSTACK,
            'status' => Payment::STATUS_PENDING,
        ]);

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.paystack.co/transaction/initialize'
            && $r['email'] === 'tola@example.test'
            && $r['amount'] === (int) round($order->total * 100)
            && $r['currency'] === 'NGN');
    }

    public function test_webhook_applies_paystack_payment_and_is_idempotent(): void
    {
        $secret = 'sk_test_webhook_secret';
        config(['services.paystack.secret_key' => $secret]);

        $order = $this->placeOrder();
        $amountKobo = (int) round($order->total * 100);

        $payment = PaymentService::recordPayment(
            type: 'payment_in',
            amount: (float) $order->total,
            paymentMethod: 'paystack',
            documentType: 'order',
            documentId: $order->id,
            customerId: null,
            supplierId: null,
            reference: null,
            userId: null,
            gateway: Payment::GATEWAY_PAYSTACK,
            status: Payment::STATUS_PENDING,
        );
        $payment->update(['gateway_reference' => $payment->ref_id]);
        $reference = $payment->ref_id;

        Http::fake([
            'api.paystack.co/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success', 'reference' => $reference, 'amount' => $amountKobo, 'paid_at' => now()->toISOString()],
            ]),
        ]);

        $payload = ['event' => 'charge.success', 'data' => ['reference' => $reference, 'amount' => $amountKobo, 'status' => 'success']];
        $body = json_encode($payload);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, $secret),
        ];

        $this->call('POST', '/webhooks/paystack', [], [], [], $headers, $body)->assertOk();

        $this->assertSame(Payment::STATUS_SUCCESS, $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->status);

        // Retried webhook must not double-apply.
        $this->call('POST', '/webhooks/paystack', [], [], [], $headers, $body)->assertOk();
        $this->assertSame(Payment::STATUS_SUCCESS, $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_admin_orders_index_lists_orders_and_requires_auth(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = $this->owner();
        $order = $this->placeOrder();

        $this->get('/admin/orders')->assertRedirect('/login');

        $response = $this->actingAs($owner)->get('/admin/orders');
        $response->assertOk();

        $page = $this->dataPage($response);
        $this->assertSame($order->ref_id, $page['props']['orders']['data'][0]['ref_id']);
        $this->assertSame(15000.0, (float) $page['props']['orders']['data'][0]['total']);
    }
}
