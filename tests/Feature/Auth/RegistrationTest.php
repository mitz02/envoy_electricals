<?php

namespace Tests\Feature\Auth;

use App\Mail\UserWelcomeMail;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_as_buyers(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('buyer.dashboard'));

        $user = auth()->user();
        $this->assertSame('buyer', $user->role?->slug);
        $this->assertNotNull($user->customer, 'A registered buyer should get a linked customer record.');

        Mail::assertSent(UserWelcomeMail::class, function (UserWelcomeMail $mail) use ($user) {
            return $mail->hasTo($user->email) && $mail->variant === 'buyer';
        });
    }

    public function test_registration_reuses_an_existing_buyer_role(): void
    {
        $buyerRole = Role::where('slug', 'buyer')->firstOrFail();

        $this->post('/register', [
            'name' => 'Another Buyer',
            'email' => 'buyer@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertSame($buyerRole->id, auth()->user()->role_id);
    }
}
