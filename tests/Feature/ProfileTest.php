<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        // The column defaults to true in the schema, so hydrate like a real
        // request does rather than reading the un-hydrated factory instance.
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user->fresh())
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Edit')
                ->where('isActive', true)
                ->has('createdAt')
            );
    }

    public function test_profile_page_reports_a_suspended_account(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user->fresh())
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('isActive', false));
    }

    /**
     * `User` does not implement MustVerifyEmail, so the page must not offer a
     * re-send link: EmailVerificationNotificationController calls
     * hasVerifiedEmail(), which would fatal.
     */
    public function test_profile_page_does_not_offer_email_verification(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->missing('mustVerifyEmail')
                ->missing('status')
            );
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_phone_number_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => '+234 800 123 4567',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('+234 800 123 4567', $user->fresh()->phone);
    }

    public function test_phone_number_may_be_cleared(): void
    {
        $user = User::factory()->create(['phone' => '+234 800 123 4567']);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => null,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNull($user->fresh()->phone);
    }

    public function test_phone_number_is_length_capped(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => str_repeat('9', 31),
            ])
            ->assertSessionHasErrors('phone');
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
