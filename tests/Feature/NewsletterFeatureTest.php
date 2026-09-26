<?php

namespace Tests\Feature;

use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\NewsletterSubscriber;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NewsletterFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_manage_newsletters_and_subscribers(): void
    {
        $owner = User::factory()->create(['role_id' => Role::where('slug', 'owner')->firstOrFail()->id]);

        $this->actingAs($owner)
            ->get(route('admin.marketing.newsletters.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Marketing/Newsletters/Index'));

        $this->actingAs($owner)
            ->get(route('admin.marketing.subscribers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Marketing/Subscribers'));
    }

    public function test_admin_can_add_a_subscriber_manually(): void
    {
        $owner = User::factory()->create(['role_id' => Role::where('slug', 'owner')->firstOrFail()->id]);

        $this->actingAs($owner)
            ->post(route('admin.marketing.subscribers.store'), [
                'email' => 'annie@example.com',
                'name' => 'Annie',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'annie@example.com',
            'name' => 'Annie',
            'status' => 'subscribed',
        ]);
    }

    public function test_duplicate_email_resubscribes_instead_of_duplicating(): void
    {
        $owner = User::factory()->create(['role_id' => Role::where('slug', 'owner')->firstOrFail()->id]);

        NewsletterSubscriber::create(['email' => 'annette@example.com', 'status' => 'unsubscribed']);

        $this->actingAs($owner)
            ->post(route('admin.marketing.subscribers.store'), [
                'email' => 'ANNETTE@example.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('newsletter_subscribers', 1);
        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'annette@example.com',
            'status' => 'subscribed',
        ]);
    }

    public function test_newsletter_can_be_created_and_sent_to_subscribers(): void
    {
        Mail::fake();

        $owner = User::factory()->create(['role_id' => Role::where('slug', 'owner')->firstOrFail()->id]);

        $subs = [
            NewsletterSubscriber::create(['email' => 'one@example.com', 'name' => 'One']),
            NewsletterSubscriber::create(['email' => 'two@example.com', 'status' => 'subscribed']),
        ];
        NewsletterSubscriber::create(['email' => 'gone@example.com', 'status' => 'unsubscribed']);

        $response = $this->actingAs($owner)
            ->post(route('admin.marketing.newsletters.store'), [
                'subject' => 'December Solar Deals',
                'content' => "New inverter bundles are here.\n50% off installation.",
                'status' => 'draft',
            ]);

        $newsletter = Newsletter::firstWhere('subject', 'December Solar Deals');
        $this->assertNotNull($newsletter);
        $response->assertRedirect(route('admin.marketing.newsletters.show', $newsletter->id));

        $this->actingAs($owner)
            ->post(route('admin.marketing.newsletters.send', $newsletter->id))
            ->assertRedirect(route('admin.marketing.newsletters.show', $newsletter->id));

        Mail::assertSent(NewsletterMail::class, 2);

        $this->assertDatabaseHas('newsletters', [
            'id' => $newsletter->id,
            'status' => 'sent',
        ]);

        $newsletter->refresh();
        $this->assertNotNull($newsletter->sent_at);
    }

    public function test_sending_with_no_subscribers_does_not_mark_newsletter_sent(): void
    {
        $owner = User::factory()->create(['role_id' => Role::where('slug', 'owner')->firstOrFail()->id]);

        $newsletter = Newsletter::create([
            'subject' => 'Quiet Campaign',
            'content' => 'Nobody home yet.',
            'status' => 'draft',
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->post(route('admin.marketing.newsletters.send', $newsletter->id))
            ->assertRedirect(route('admin.marketing.newsletters.show', $newsletter->id));

        $this->assertDatabaseHas('newsletters', [
            'id' => $newsletter->id,
            'status' => 'draft',
            'sent_at' => null,
        ]);
    }

    public function test_sent_newsletter_cannot_be_sent_again(): void
    {
        $owner = User::factory()->create(['role_id' => Role::where('slug', 'owner')->firstOrFail()->id]);

        $newsletter = Newsletter::create([
            'subject' => 'One-shot Newsletter',
            'content' => 'Sent once.',
            'status' => 'sent',
            'sent_at' => now(),
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->post(route('admin.marketing.newsletters.send', $newsletter->id))
            ->assertStatus(409);

        $this->actingAs($owner)
            ->get(route('admin.marketing.newsletters.edit', $newsletter->id))
            ->assertStatus(409);

        $this->actingAs($owner)
            ->put(route('admin.marketing.newsletters.update', $newsletter->id), [
                'subject' => 'Hacked',
                'content' => 'Trying to re-edit.',
                'status' => 'draft',
            ])
            ->assertStatus(409);

        $this->assertDatabaseHas('newsletters', [
            'id' => $newsletter->id,
            'subject' => 'One-shot Newsletter',
            'status' => 'sent',
        ]);
    }

    public function test_scheduled_newsletter_requires_a_date(): void
    {
        $owner = User::factory()->create(['role_id' => Role::where('slug', 'owner')->firstOrFail()->id]);

        $this->actingAs($owner)
            ->post(route('admin.marketing.newsletters.store'), [
                'subject' => 'Scheduled Campaign',
                'content' => 'Runs soon.',
                'status' => 'scheduled',
                'scheduled_at' => null,
            ])
            ->assertSessionHasErrors('scheduled_at');
    }

    public function test_public_subscription_and_unsubscribe_flow(): void
    {
        $this->post(route('newsletter.store'), [
            'email' => 'fola@example.com',
            'name' => 'Fola',
        ])->assertRedirect();

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'fola@example.com',
            'name' => 'Fola',
            'status' => 'subscribed',
        ]);

        $subscriber = NewsletterSubscriber::firstWhere('email', 'fola@example.com');
        $this->assertNotEmpty($subscriber->token);

        $this->get(route('newsletter.unsubscribe', $subscriber->token))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Newsletter/Unsubscribed'));

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'fola@example.com',
            'status' => 'unsubscribed',
        ]);
    }

    public function test_invalid_unsubscribe_token_shows_invalid_state(): void
    {
        $this->get(route('newsletter.unsubscribe', 'does-not-exist'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Newsletter/Unsubscribed')
                ->where('status', 'invalid'));
    }
}
