<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\PaymentService;
use App\Services\PurchaseService;
use App\Services\SaleService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->first()->id,
        ]);
    }

    protected function product(string $name): Product
    {
        return Product::create([
            'ref_id' => 'EV-PROD-'.str_pad((string) random_int(1000, PHP_INT_MAX), 6, '0', STR_PAD_LEFT),
            'name' => $name,
            'sku' => 'PAY-'.strtoupper(substr(md5((string) mt_rand()), 0, 8)),
            'unit' => 'piece',
            'selling_price' => 120000,
        ]);
    }

    protected function saleWithBalance(User $user, float $total = 240000): Sale
    {
        $inventory = app(InventoryService::class);
        $saleService = app(SaleService::class);
        $customer = Customer::create([
            'ref_id' => 'CUS-TEST-PAY',
            'name' => 'Mr Balance',
            'email' => 'bal@customer.test',
            'phone' => '08000000001',
            'customer_type' => 'regular',
        ]);
        $product = $this->product('PV Panel 550W');
        $inventory->setOpeningStock($product, 10, 95000);

        return $saleService->createSale(
            ['sale_date' => now()->toDateString(), 'customer_id' => $customer->id, 'amount_paid' => 0],
            [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => $total / 2]],
            $user->id
        );
    }

    public function test_manual_payment_records_and_reduces_sale_balance(): void
    {
        $owner = $this->owner();
        $sale = $this->saleWithBalance($owner);

        $this->from("/admin/sales/{$sale->id}")
            ->actingAs($owner)
            ->post('/admin/payments', [
                'document_type' => 'sale',
                'document_id' => $sale->id,
                'amount' => 50000,
                'payment_method' => 'cash',
                'payment_date' => now()->toDateString(),
                'remarks' => 'Customer paid on pickup',
            ])
            ->assertRedirect();

        $sale->refresh();
        $this->assertSame(50000.0, (float) $sale->amount_paid);
        $this->assertSame(190000.0, (float) $sale->balance);

        $this->assertDatabaseHas('payments', [
            'document_type' => 'sale',
            'document_id' => $sale->id,
            'amount' => 50000,
            'type' => 'payment_in',
            'status' => Payment::STATUS_SUCCESS,
            'gateway' => Payment::GATEWAY_LOCAL,
        ]);
    }

    public function test_manual_payment_cannot_exceed_balance(): void
    {
        $owner = $this->owner();
        $sale = $this->saleWithBalance($owner);

        $this->from("/admin/sales/{$sale->id}")
            ->actingAs($owner)
            ->post('/admin/payments', [
                'document_type' => 'sale',
                'document_id' => $sale->id,
                'amount' => 999999,
                'payment_method' => 'cash',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0.0, (float) $sale->fresh()->amount_paid);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_supplier_payment_reduces_purchase_balance(): void
    {
        $owner = $this->owner();
        $supplier = Supplier::create(['ref_id' => 'SUP-TEST-PAY', 'name' => 'Solar Wholesale Ltd']);
        $product = $this->product('Inverter');
        $purchase = app(PurchaseService::class)->createPurchase(
            ['purchase_date' => now()->toDateString(), 'supplier_id' => $supplier->id, 'amount_paid' => 0],
            [['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 80000]],
            $owner->id
        );

        $this->from("/admin/purchases/{$purchase->id}")
            ->actingAs($owner)
            ->post('/admin/payments', [
                'document_type' => 'purchase',
                'document_id' => $purchase->id,
                'amount' => 300000,
                'payment_method' => 'bank_transfer',
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertSame(300000.0, (float) $purchase->fresh()->amount_paid);
        $this->assertSame(500000.0, (float) $purchase->fresh()->balance);
        $this->assertDatabaseHas('payments', [
            'document_type' => 'purchase',
            'document_id' => $purchase->id,
            'amount' => 300000,
            'type' => 'payment_out',
            'gateway' => Payment::GATEWAY_LOCAL,
        ]);
    }

    public function test_paystack_link_creates_pending_payment(): void
    {
        config(['services.paystack.secret_key' => 'sk_test_secret']);

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'message' => 'Authorization URL created',
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/abc123',
                    'access_code' => 'abc123',
                    'reference' => 'PAY-2026-000001',
                ],
            ]),
        ]);

        $owner = $this->owner();
        $sale = $this->saleWithBalance($owner);

        $response = $this->actingAs($owner)->postJson('/admin/payments/paystack', [
            'document_type' => 'sale',
            'document_id' => $sale->id,
        ]);

        $response->assertOk()
            ->assertJson(['authorization_url' => 'https://checkout.paystack.com/abc123']);

        $this->assertDatabaseHas('payments', [
            'document_type' => 'sale',
            'document_id' => $sale->id,
            'amount' => 240000,
            'gateway' => Payment::GATEWAY_PAYSTACK,
            'status' => Payment::STATUS_PENDING,
        ]);

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.paystack.co/transaction/initialize'
            && $r['amount'] === 24000000
            && $r['currency'] === 'NGN'
            && $r['email'] === 'bal@customer.test'
            && $r['reference'] === 'PAY-2026-000001');
    }

    public function test_webhook_verifies_and_applies_payment_and_is_idempotent(): void
    {
        $secret = 'sk_test_webhook_secret';
        config(['services.paystack.secret_key' => $secret]);

        $owner = $this->owner();
        $sale = $this->saleWithBalance($owner);

        $payment = PaymentService::recordPayment(
            type: 'payment_in',
            amount: (float) $sale->balance,
            paymentMethod: 'paystack',
            documentType: 'sale',
            documentId: $sale->id,
            customerId: $sale->customer_id,
            supplierId: null,
            reference: null,
            userId: $owner->id,
            gateway: Payment::GATEWAY_PAYSTACK,
            status: Payment::STATUS_PENDING,
        );
        $payment->update(['gateway_reference' => $payment->ref_id]);
        $reference = $payment->ref_id;

        Http::fake([
            'api.paystack.co/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success', 'reference' => $reference, 'amount' => 24000000, 'paid_at' => now()->toISOString()],
            ]),
        ]);

        $webhook = function () use ($reference) {
            $payload = [
                'event' => 'charge.success',
                'data' => ['reference' => $reference, 'amount' => 24000000, 'status' => 'success'],
            ];
            $body = json_encode($payload);
            $signature = hash_hmac('sha512', $body, config('services.paystack.secret_key'));

            return $this->call('POST', '/webhooks/paystack', [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
            ], $body);
        };

        // First delivery applies the payment.
        $webhook()->assertOk();
        $this->assertSame(Payment::STATUS_SUCCESS, $payment->fresh()->status);
        $this->assertSame(240000.0, (float) $sale->fresh()->amount_paid);
        $this->assertSame(0.0, (float) $sale->fresh()->balance);

        // Retried webhook must not double-apply (idempotency).
        $webhook()->assertOk();
        $this->assertSame(240000.0, (float) $sale->fresh()->amount_paid);
        $this->assertSame(0.0, (float) $sale->fresh()->balance);
    }

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        config(['services.paystack.secret_key' => 'sk_test_webhook_secret']);

        $payload = ['event' => 'charge.success', 'data' => ['reference' => 'X', 'amount' => 100]];

        $this->call('POST', '/webhooks/paystack', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => 'bad-signature',
        ], json_encode($payload))
            ->assertStatus(400);
    }

    public function test_webhook_for_unknown_reference_is_ignored(): void
    {
        $secret = 'sk_test_webhook_secret';
        config(['services.paystack.secret_key' => $secret]);

        $payload = ['event' => 'charge.success', 'data' => ['reference' => 'UNKNOWN-REF', 'amount' => 100]];
        $body = json_encode($payload);

        $this->call('POST', '/webhooks/paystack', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, $secret),
        ], $body)
            ->assertOk();
    }
}
