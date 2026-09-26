<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerStoreAssignmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Store, 1: Store}
     */
    protected function stores(): array
    {
        $lagos = Store::updateOrCreate(['code' => 'LAG'], [
            'name' => 'Lagos Main Branch',
            'is_default' => true,
        ]);
        $abuja = Store::updateOrCreate(['code' => 'ABJ'], [
            'name' => 'Abuja Branch',
            'is_default' => false,
        ]);

        return [$lagos, $abuja];
    }

    protected function owner(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->firstOrFail()->id,
        ]);
    }

    public function test_self_registered_buyer_customer_is_not_assigned_to_a_store(): void
    {
        Mail::fake();
        $this->stores();

        $this->post('/register', [
            'name' => 'New Buyer',
            'email' => 'newbuyer@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('buyer.dashboard'));

        $buyer = User::where('email', 'newbuyer@example.com')->firstOrFail();

        $this->assertDatabaseHas('customers', [
            'user_id' => $buyer->id,
            'store_id' => null,
        ]);
    }

    public function test_unassigned_buyer_customer_is_visible_under_all_stores(): void
    {
        Mail::fake();
        $this->stores();
        $this->post('/register', [
            'name' => 'Visible Buyer',
            'email' => 'visible@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $owner = $this->owner();

        $this->actingAs($owner)
            ->get('/admin/customers')
            ->assertOk()
            ->assertSee('Visible Buyer');
    }

    public function test_admin_can_assign_a_store_when_creating_a_customer(): void
    {
        [$lagos] = $this->stores();
        $owner = $this->owner();

        $this->actingAs($owner)->post('/admin/customers', [
            'name' => 'Assigned Customer',
            'phone' => '08011112222',
            'customer_type' => 'regular',
            'store_id' => $lagos->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'name' => 'Assigned Customer',
            'store_id' => $lagos->id,
        ]);
    }

    public function test_admin_can_create_a_customer_as_unassigned(): void
    {
        $this->stores();
        $owner = $this->owner();

        $this->actingAs($owner)->post('/admin/customers', [
            'name' => 'Unassigned Customer',
            'customer_type' => 'regular',
            'store_id' => '',
        ])->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'name' => 'Unassigned Customer',
            'store_id' => null,
        ]);
    }

    public function test_admin_can_assign_and_clear_a_customers_store(): void
    {
        [$lagos, $abuja] = $this->stores();

        $customer = new Customer([
            'ref_id' => 'CUS-TEST-'.mt_rand(10000, 99999),
            'name' => 'Reassign Me',
            'customer_type' => 'walk_in',
        ]);
        $customer->skipStoreAutoAssign = true;
        $customer->save();

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'store_id' => null]);

        $owner = $this->owner();

        $this->actingAs($owner)->put("/admin/customers/{$customer->id}", [
            'name' => 'Reassign Me',
            'customer_type' => 'walk_in',
            'store_id' => $abuja->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'store_id' => $abuja->id]);

        $this->actingAs($owner)->put("/admin/customers/{$customer->id}", [
            'name' => 'Reassign Me',
            'customer_type' => 'walk_in',
            'store_id' => '',
        ])->assertRedirect();

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'store_id' => null]);
    }

    public function test_restricted_staff_cannot_assign_a_branch_they_cannot_access(): void
    {
        [$lagos, $abuja] = $this->stores();
        $this->seed(RolesAndPermissionsSeeder::class);

        $manager = User::factory()->create([
            'role_id' => Role::where('slug', 'manager')->firstOrFail()->id,
        ]);
        $manager->syncAllowedStores([$lagos->id]);

        $this->actingAs($manager)->post('/admin/customers', [
            'name' => 'Cross Branch Customer',
            'customer_type' => 'regular',
            'store_id' => $abuja->id,
        ])->assertSessionHasErrors('store_id');

        $this->assertDatabaseMissing('customers', ['name' => 'Cross Branch Customer']);

        $this->actingAs($manager)->post('/admin/customers', [
            'name' => 'Own Branch Customer',
            'customer_type' => 'regular',
            'store_id' => $lagos->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('customers', ['name' => 'Own Branch Customer', 'store_id' => $lagos->id]);
    }

    public function test_buyer_customer_created_via_admin_buyer_edit_is_unassigned(): void
    {
        $this->stores();
        $owner = $this->owner();

        $buyer = User::factory()->create([
            'role_id' => Role::where('slug', 'buyer')->firstOrFail()->id,
            'name' => 'No Customer Yet',
            'email' => 'nocust@example.com',
        ]);

        $this->actingAs($owner)->put(route('admin.buyers.update', $buyer), [
            'name' => 'No Customer Yet',
            'email' => 'nocust@example.com',
            'phone' => '08000000000',
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'user_id' => $buyer->id,
            'name' => 'No Customer Yet',
            'store_id' => null,
        ]);
    }
}
