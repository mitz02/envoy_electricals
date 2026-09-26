<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\Trainee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_staff_with_a_trainee_record_is_redirected_to_admin_dashboard(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::where('slug', 'manager')->firstOrFail();

        $user = User::factory()->create(['role_id' => $role->id]);

        Trainee::create([
            'ref_id' => 'TRN-TEST-001',
            'user_id' => $user->id,
            'type' => Trainee::TYPE_TRAINEE,
            'name' => $user->name,
            'email' => $user->email,
            'status' => Trainee::STATUS_ACTIVE,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_super_admin_with_a_trainee_record_is_redirected_to_admin_dashboard(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::where('slug', 'owner')->firstOrFail();

        $user = User::factory()->create(['role_id' => $role->id]);

        Trainee::create([
            'ref_id' => 'TRN-TEST-002',
            'user_id' => $user->id,
            'type' => Trainee::TYPE_TRAINEE,
            'name' => $user->name,
            'email' => $user->email,
            'status' => Trainee::STATUS_ACTIVE,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_pure_trainee_is_redirected_to_portal_dashboard(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::where('slug', 'trainee')->firstOrFail();

        $user = User::factory()->create(['role_id' => $role->id]);

        Trainee::create([
            'ref_id' => 'TRN-TEST-003',
            'user_id' => $user->id,
            'type' => Trainee::TYPE_TRAINEE,
            'name' => $user->name,
            'email' => $user->email,
            'status' => Trainee::STATUS_ACTIVE,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('portal.dashboard', absolute: false));
    }

    public function test_buyer_is_redirected_to_buyer_dashboard(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::where('slug', 'buyer')->firstOrFail();

        $user = User::factory()->create([
            'role_id' => $role->id,
            'name' => 'Jane Buyer',
            'email' => 'jane@example.com',
        ]);

        $this->post('/login', [
            'email' => 'jane@example.com',
            'password' => 'password',
        ])->assertRedirect(route('buyer.dashboard', absolute: false));
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
