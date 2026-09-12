<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOutstandingTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): User
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->first()->id,
        ]);
    }

    protected function product(string $name): Product
    {
        $product = Product::create([
            'ref_id' => 'OVS-PROD-' . mt_rand(10000, 99999),
            'name' => $name,
            'sku' => 'OVS-' . strtoupper(substr(md5($name . mt_rand()), 0, 8)),
            'unit' => 'piece',
            'selling_price' => 0,
        ]);

        app(InventoryService::class)->setOpeningStock($product, 20, 1000);

        return $product;
    }

    protected function customer(string $name): Customer
    {
        return Customer::create([
            'ref_id' => 'OVS-CUS-' . mt_rand(10000, 99999),
            'name' => $name,
            'phone' => '080' . mt_rand(1000000, 9999999),
            'customer_type' => 'regular',
        ]);
    }

    protected function createSale(Customer $customer, float $total, float $paid): void
    {
        app(SaleService::class)->createSale(
            [
                'sale_date' => now()->toDateString(),
                'customer_id' => $customer->id,
                'amount_paid' => $paid,
                'payment_method' => 'cash',
                'remarks' => 'outstanding test',
            ],
            [['product_id' => $this->product($customer->name . ' product')->id, 'quantity' => 1, 'unit_price' => $total]],
            $this->owner()->id,
        );
    }

    protected function customerOwing(string $name, float $total = 50000, float $paid = 30000): Customer
    {
        $customer = $this->customer($name);
        $this->createSale($customer, $total, $paid);

        return $customer;
    }

    private function dataPage(\Illuminate\Testing\TestResponse $response): array
    {
        if (preg_match('/data-page="([^"]*)"/', $response->getContent(), $m)) {
            return json_decode(html_entity_decode($m[1], ENT_QUOTES), true) ?? [];
        }

        $this->fail('Unable to parse Inertia data-page attribute. Status: ' . $response->getStatusCode() . ' Exception: ' . ($response->exception ? $response->exception->getMessage() : 'none'));
    }

    public function test_customer_outstanding_is_the_sum_of_unpaid_sale_balances(): void
    {
        $customer = $this->customer('Ada Obi');

        $this->createSale($customer, 50000, 30000); // balance 20000
        $this->createSale($customer, 10000, 10000); // balance 0

        $this->assertSame(20000.0, $customer->outstandingBalance());
        $this->assertTrue($customer->refresh()->has_outstanding);

        $customerClear = $this->customer('Bola Ridge');
        $this->createSale($customerClear, 8000, 8000);

        $this->assertSame(0.0, $customerClear->outstandingBalance());
        $this->assertFalse($customerClear->has_outstanding);
    }

    public function test_customer_index_lists_outstanding_and_owing_filter_works(): void
    {
        $owner = $this->owner();
        $owing = $this->customerOwing('Ada Obi');
        $clear = $this->customerOwing('Bola Ridge', 8000, 8000);

        $this->actingAs($owner)->get('/admin/customers')->assertOk();

        $page = $this->dataPage($this->actingAs($owner)->get('/admin/customers'));

        $names = collect($page['props']['customers']['data'])->pluck('name')->all();
        $this->assertContains('Ada Obi', $names);
        $this->assertContains('Bola Ridge', $names);

        $adaRow = collect($page['props']['customers']['data'])->firstWhere('name', 'Ada Obi');
        $this->assertSame(20000.0, (float) $adaRow['outstanding']);

        $this->assertSame(20000.0, (float) $page['props']['summary']['total_outstanding']);
        $this->assertSame(1, $page['props']['summary']['owing_customers']);

        $filtered = $this->dataPage($this->actingAs($owner)->get('/admin/customers?owing=1'));
        $names = collect($filtered['props']['customers']['data'])->pluck('name')->all();
        $this->assertContains('Ada Obi', $names);
        $this->assertNotContains('Bola Ridge', $names);
        $this->assertSame(['owing' => '1'], $filtered['props']['filters']);
    }

    public function test_sale_form_flags_customers_with_outstanding_balances(): void
    {
        $owner = $this->owner();
        $owing = $this->customerOwing('Ada Obi');
        $clear = $this->customerOwing('Bola Ridge', 8000, 8000);

        $page = $this->dataPage($this->actingAs($owner)->get('/admin/sales/create'));

        $ada = collect($page['props']['customers'])->firstWhere('id', $owing->id);
        $this->assertSame(20000.0, (float) $ada['outstanding']);
        $this->assertTrue($ada['has_outstanding']);

        $bola = collect($page['props']['customers'])->firstWhere('id', $clear->id);
        $this->assertSame(0.0, (float) $bola['outstanding']);
        $this->assertFalse($bola['has_outstanding']);
    }

    public function test_storing_a_sale_warns_when_customer_owes_previous_debt(): void
    {
        $owner = $this->owner();
        $customer = $this->customerOwing('Ada Obi');
        $newProduct = $this->product('New Inverter');

        $this->actingAs($owner)
            ->post('/admin/sales', [
                'sale_date' => now()->toDateString(),
                'customer_id' => $customer->id,
                'items' => [['product_id' => $newProduct->id, 'quantity' => 1, 'unit_price' => 7000]],
                'amount_paid' => 0,
                'payment_method' => 'cash',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', fn (string $m) => str_contains($m, 'owes'));

        $this->assertSame(27000.0, $customer->refresh()->outstandingBalance());
    }

    public function test_storing_a_sale_has_no_warning_for_customers_who_are_clear(): void
    {
        $owner = $this->owner();
        $customer = $this->customerOwing('Bola Ridge', 8000, 8000);
        $newProduct = $this->product('New Cable');

        $this->actingAs($owner)
            ->post('/admin/sales', [
                'sale_date' => now()->toDateString(),
                'customer_id' => $customer->id,
                'items' => [['product_id' => $newProduct->id, 'quantity' => 1, 'unit_price' => 3000]],
                'amount_paid' => 3000,
                'payment_method' => 'cash',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Sale completed.');

        $this->assertSame(0.0, $customer->refresh()->has_outstanding ? $customer->outstandingBalance() : $customer->outstandingBalance());
        $this->assertFalse($customer->refresh()->has_outstanding);
    }

    public function test_sale_index_reports_customer_outstanding(): void
    {
        $owner = $this->owner();
        $customer = $this->customerOwing('Ada Obi');
        $this->createSale($customer, 10000, 10000); // balance 0

        $sale = $customer->sales()->first();
        $page = $this->dataPage($this->actingAs($owner)->get("/admin/sales/{$sale->id}"));

        $this->assertSame(20000.0, (float) $page['props']['sale']['customer']['outstanding']);

        $index = $this->dataPage($this->actingAs($owner)->get('/admin/sales'));
        $firstSale = collect($index['props']['sales']['data'])->firstWhere('id', $sale->id);
        $this->assertSame(20000.0, (float) $firstSale['customer']['outstanding']);
    }
}