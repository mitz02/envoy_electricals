<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerQuickCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): User
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->first()->id,
        ]);
    }

    public function test_owner_can_quick_create_a_customer(): void
    {
        $this->actingAs($this->owner())
            ->postJson('/admin/customers/quick', [
                'name' => 'Ada Obi',
                'phone' => '08031234567',
                'email' => 'ada@example.com',
                'location' => 'Lagos',
                'address' => '12 Marina',
                'customer_type' => 'regular',
            ])
            ->assertOk()
            ->assertJsonPath('customer.name', 'Ada Obi');

        $this->assertDatabaseHas('customers', [
            'name' => 'Ada Obi',
            'phone' => '08031234567',
            'email' => 'ada@example.com',
            'customer_type' => 'regular',
        ]);
        $this->assertNotNull(Customer::where('name', 'Ada Obi')->first()->ref_id);
    }

    public function test_quick_create_requires_a_name(): void
    {
        $this->actingAs($this->owner())
            ->postJson('/admin/customers/quick', ['phone' => '08000000000'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_quick_create_rejects_invalid_customer_type(): void
    {
        $this->actingAs($this->owner())
            ->postJson('/admin/customers/quick', [
                'name' => 'Bad Type',
                'customer_type' => 'vip',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('customer_type');
    }

    public function test_quick_create_requires_authentication(): void
    {
        $this->postJson('/admin/customers/quick', ['name' => 'No Auth'])
            ->assertUnauthorized();

        $this->assertDatabaseCount('customers', 0);
    }
}