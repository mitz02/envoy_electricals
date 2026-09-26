<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\ReferenceGenerator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BuyerAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function buyer(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('slug', 'buyer')->firstOrFail()->id,
            'name' => 'Jane Buyer',
            'email' => 'jane@example.com',
        ]);
    }

    protected function placeOrder(int $userId, string $name = 'Jane Buyer', float $total = 15000): Order
    {
        $order = Order::create([
            'ref_id' => 'ORD-TEST-'.mt_rand(10000, 99999),
            'user_id' => $userId,
            'subtotal' => $total,
            'discount' => 0,
            'tax' => 0,
            'delivery_fee' => 0,
            'total' => $total,
            'status' => 'pending',
            'customer_name' => $name,
            'customer_phone' => '08012345678',
            'customer_email' => 'jane@example.com',
            'delivery_address' => '14 Akin Street, Ikeja, Lagos',
            'fulfillment' => 'pending',
        ]);

        return $order;
    }

    private function dataPage(TestResponse $response): array
    {
        if (preg_match('/data-page="([^"]*)"/', $response->getContent(), $m)) {
            return json_decode(html_entity_decode($m[1], ENT_QUOTES), true) ?? [];
        }

        $this->fail('Unable to parse Inertia data-page attribute. Status: '.$response->getStatusCode().' Exception: '.($response->exception ? $response->exception->getMessage() : 'none'));
    }

    public function test_buyer_dashboard_requires_authentication(): void
    {
        $this->get('/account')->assertRedirect('/login');
        $this->get('/account/orders')->assertRedirect('/login');
    }

    public function test_buyer_sees_only_their_own_orders_on_dashboard(): void
    {
        $buyer = $this->buyer();
        $mine = $this->placeOrder($buyer->id, total: 25000);

        $other = User::factory()->create(['role_id' => Role::where('slug', 'buyer')->firstOrFail()->id]);
        $theirs = $this->placeOrder($other->id, total: 90000);

        $response = $this->actingAs($buyer)->get('/account');
        $response->assertOk();

        $page = $this->dataPage($response);
        $refs = array_column($page['props']['recent_orders'], 'ref_id');
        $this->assertContains($mine->ref_id, $refs);
        $this->assertNotContains($theirs->ref_id, $refs);

        $this->assertSame(1, $page['props']['stats']['orders_count']);
        $this->assertSame(25000.0, (float) $page['props']['stats']['total_spent']);
        $this->assertSame(25000.0, (float) $page['props']['stats']['outstanding']);

        $this->assertSame('Jane Buyer', $page['props']['user']['name']);
    }

    public function test_buyer_orders_page_lists_their_orders(): void
    {
        $buyer = $this->buyer();
        $this->placeOrder($buyer->id);

        $response = $this->actingAs($buyer)->get('/account/orders');
        $response->assertOk();

        $page = $this->dataPage($response);
        $this->assertCount(1, $page['props']['orders']['data']);
        $this->assertSame(15000.0, (float) $page['props']['orders']['data'][0]['total']);
    }

    public function test_staff_are_redirected_away_from_buyer_pages(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->firstOrFail()->id,
        ]);

        $this->actingAs($owner)->get('/account')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($owner)->get('/account/orders')->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_buyers_page_lists_registered_buyers(): void
    {
        $buyer = $this->buyer();
        $this->placeOrder($buyer->id);

        $owner = User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->firstOrFail()->id,
        ]);

        $response = $this->actingAs($owner)->get('/admin/buyers');
        $response->assertOk();

        $page = $this->dataPage($response);
        $this->assertSame('Jane Buyer', $page['props']['buyers']['data'][0]['name']);
        $this->assertSame(1, $page['props']['buyers']['data'][0]['orders_count']);
        $this->assertSame(15000.0, (float) $page['props']['buyers']['data'][0]['orders_total']);
    }

    public function test_buyers_cannot_access_admin_area(): void
    {
        $buyer = $this->buyer();

        $this->actingAs($buyer)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_impersonate_a_buyer_and_return(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->firstOrFail()->id,
        ]);
        $buyer = User::factory()->create([
            'role_id' => Role::where('slug', 'buyer')->firstOrFail()->id,
            'name' => 'Ada Impersonated',
        ]);

        $this->actingAs($owner)
            ->post(route('admin.buyers.impersonate', $buyer))
            ->assertRedirect(route('buyer.dashboard'))
            ->assertSessionHas('impersonator_id', $owner->id);

        $this->withSession(['impersonator_id' => $owner->id])
            ->actingAs($buyer)
            ->post(route('buyer.impersonation.leave'))
            ->assertRedirect(route('admin.buyers.index'));

        $this->assertAuthenticatedAs($owner);
    }

    public function test_admin_can_edit_a_buyer_and_linked_customer_stays_in_sync(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $buyer = User::factory()->create([
            'role_id' => Role::where('slug', 'buyer')->firstOrFail()->id,
            'name' => 'Chioma Buyer',
            'email' => 'chioma@example.com',
        ]);
        $customer = $buyer->customer()->create([
            'ref_id' => ReferenceGenerator::generate('customer'),
            'name' => 'Chioma Buyer',
            'email' => 'chioma@example.com',
            'customer_type' => 'regular',
        ]);

        $owner = User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->firstOrFail()->id,
        ]);

        $this->actingAs($owner)
            ->put(route('admin.buyers.update', $buyer), [
                'name' => 'Chioma Updated',
                'email' => 'chioma.new@example.com',
                'phone' => '08098765432',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.buyers.show', $buyer));

        $this->assertDatabaseHas('users', [
            'id' => $buyer->id,
            'name' => 'Chioma Updated',
            'email' => 'chioma.new@example.com',
            'phone' => '08098765432',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Chioma Updated',
            'email' => 'chioma.new@example.com',
            'phone' => '08098765432',
        ]);
    }

    public function test_admin_can_delete_a_buyer_but_keeps_customer_and_history(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $buyer = User::factory()->create([
            'role_id' => Role::where('slug', 'buyer')->firstOrFail()->id,
        ]);
        $customer = $buyer->customer()->create([
            'ref_id' => ReferenceGenerator::generate('customer'),
            'name' => 'Deleting Buyer',
            'email' => $buyer->email,
            'customer_type' => 'regular',
        ]);

        $owner = User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->firstOrFail()->id,
        ]);

        $this->actingAs($owner)
            ->delete(route('admin.buyers.destroy', $buyer))
            ->assertRedirect(route('admin.buyers.index'));

        $this->assertDatabaseMissing('users', ['id' => $buyer->id]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'user_id' => null]);
    }

    public function test_buyers_cannot_manage_buyer_accounts(): void
    {
        $buyer = $this->buyer();

        $this->actingAs($buyer)->get(route('admin.buyers.edit', $buyer))->assertForbidden();
        $this->actingAs($buyer)->put(route('admin.buyers.update', $buyer), ['name' => 'Nope'])->assertForbidden();
        $this->actingAs($buyer)->delete(route('admin.buyers.destroy', $buyer))->assertForbidden();
        $this->actingAs($buyer)->post(route('admin.buyers.impersonate', $buyer))->assertForbidden();
    }
}
