<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Trainee;
use App\Models\User;
use App\Services\AcademyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function portalUser(string $email = 'ada@portal.test'): User
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $trainee = app(AcademyService::class)->createTrainee([
            'name' => 'Ada Trainee',
            'email' => $email,
            'phone' => '08011112222',
            'type' => Trainee::TYPE_TRAINEE,
            'password' => 'password123',
            'create_login' => true,
        ], null);

        return $trainee->user;
    }

    public function test_guest_can_view_landing_register_and_login_pages(): void
    {
        $this->get('/portal')->assertOk();
        $this->get('/portal/register')->assertOk();
        $this->get('/portal/login')->assertOk();
    }

    public function test_portal_registration_creates_user_trainee_and_logs_in(): void
    {
        $this->post('/portal/register', [
            'name' => 'Bisi Trainee',
            'email' => 'bisi@portal.test',
            'phone' => '08022223333',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'type' => Trainee::TYPE_APPRENTICE,
        ])->assertRedirect(route('portal.dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'bisi@portal.test']);

        $user = User::where('email', 'bisi@portal.test')->first();
        $this->assertSame(Role::where('slug', 'trainee')->value('id'), $user->role_id);

        $this->assertDatabaseHas('trainees', [
            'email' => 'bisi@portal.test',
            'user_id' => $user->id,
            'type' => Trainee::TYPE_APPRENTICE,
            'status' => Trainee::STATUS_ACTIVE,
        ]);

        $trainee = Trainee::where('email', 'bisi@portal.test')->first();
        $this->assertStringStartsWith('TRN-', $trainee->ref_id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_portal_registration_requires_valid_type(): void
    {
        $this->post('/portal/register', [
            'name' => 'Bad Type',
            'email' => 'bad@portal.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'type' => 'vip',
        ])->assertSessionHasErrors('type');

        $this->assertDatabaseCount('trainees', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_trainee_can_login_and_logout(): void
    {
        $user = $this->portalUser();

        $this->post('/portal/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect(route('portal.dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->post('/portal/logout')
            ->assertRedirect(route('portal.landing'));

        $this->assertGuest();
    }

    public function test_portal_dashboard_requires_authentication(): void
    {
        $this->get('/portal/dashboard')->assertRedirect(route('portal.login'));
        $this->get('/portal/certificates')->assertRedirect(route('portal.login'));
    }

    public function test_trainee_role_is_denied_admin_training_section(): void
    {
        $user = $this->portalUser();

        $this->actingAs($user)->get('/admin/training')->assertForbidden();
        $this->actingAs($user)->get('/admin/trainees')->assertForbidden();
    }
}