<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Newsletter;
use App\Models\NewsletterSubscriber;
use App\Models\Testimonial;
use App\Services\AuditLogger;
use App\Services\NewsletterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MarketingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Marketing/Index', [
            'summary' => [
                'subscribers' => NewsletterSubscriber::where('status', 'subscribed')->count(),
                'newsletters' => Newsletter::count(),
                'testimonials' => Testimonial::count(),
                'published_testimonials' => Testimonial::where('is_published', true)->count(),
                'feedback' => Feedback::count(),
                'pending_feedback' => Feedback::where('status', 'new')->count(),
                'recent_subscribers' => NewsletterSubscriber::latest()->take(4)->get(),
                'recent_testimonials' => Testimonial::latest()->take(4)->get(),
                'recent_feedback' => Feedback::latest()->take(4)->get(),
            ],
        ]);
    }

    // ---------- Subscribers ----------

    public function subscribers(Request $request): Response
    {
        $subscribers = NewsletterSubscriber::query()
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('email', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%");
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Marketing/Subscribers', [
            'subscribers' => $subscribers,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function storeSubscriber(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $subscriber = NewsletterSubscriber::updateOrCreate(
            ['email' => mb_strtolower($data['email'])],
            ['name' => $data['name'] ?? null, 'status' => 'subscribed']
        );

        $auditMessage = $subscriber->wasRecentlyCreated
            ? "Added subscriber {$subscriber->email}"
            : "Re-subscribed {$subscriber->email}";

        AuditLogger::log('created', 'newsletter_subscriber', $subscriber->id, $auditMessage);

        return redirect()->back()->with('success', 'Subscriber added.');
    }

    public function toggleSubscriber(NewsletterSubscriber $subscriber)
    {
        $subscriber->update([
            'status' => $subscriber->status === 'subscribed' ? 'unsubscribed' : 'subscribed',
        ]);

        AuditLogger::log('updated', 'newsletter_subscriber', $subscriber->id, "Set {$subscriber->email} to {$subscriber->status}");

        return redirect()->back()->with('success', "Subscriber status updated to {$subscriber->status}.");
    }

    public function destroySubscriber(NewsletterSubscriber $subscriber)
    {
        $email = $subscriber->email;
        $subscriber->delete();

        AuditLogger::log('deleted', 'newsletter_subscriber', $subscriber->id, "Removed subscriber {$email}");

        return redirect()->back()->with('success', 'Subscriber removed.');
    }

    // ---------- Newsletters ----------

    public function newsletters(Request $request): Response
    {
        $newsletters = Newsletter::query()
            ->with('creator:id,name')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Marketing/Newsletters/Index', [
            'newsletters' => $newsletters,
            'filters' => $request->only(['status']),
        ]);
    }

    public function newsletterCreate(): Response
    {
        return Inertia::render('Admin/Marketing/Newsletters/Form', [
            'newsletter' => null,
        ]);
    }

    public function newsletterStore(Request $request)
    {
        $data = $this->validateNewsletter($request);

        $initialStatus = $data['status'] === 'send_now' ? 'draft' : $data['status'];

        $newsletter = Newsletter::create([
            'subject' => $data['subject'],
            'content' => $data['content'],
            'status' => $initialStatus,
            'scheduled_at' => $data['status'] === 'scheduled' ? ($data['scheduled_at'] ?? null) : null,
            'image_path' => $this->storeNewsletterImage($request),
            'created_by' => $request->user()->id,
        ]);

        AuditLogger::log('created', 'newsletter', $newsletter->id, "Created newsletter: {$newsletter->subject}");

        if ($data['status'] === 'send_now' || $request->boolean('send_now')) {
            $subscriberCount = NewsletterSubscriber::where('status', 'subscribed')->count();
            if ($subscriberCount > 0) {
                $result = app(NewsletterService::class)->send($newsletter);
                $message = "Newsletter created and sent to {$result['delivered']} of {$result['total']} subscriber(s).";
                AuditLogger::log('sent', 'newsletter', $newsletter->id, $message);

                return redirect()->route('admin.marketing.newsletters.show', $newsletter->id)->with('success', $message);
            } else {
                return redirect()->route('admin.marketing.newsletters.show', $newsletter->id)
                    ->with('warning', 'Newsletter created as draft. Could not broadcast because there are 0 active subscribers yet.');
            }
        }

        return redirect()->route('admin.marketing.newsletters.show', $newsletter->id)->with('success', 'Newsletter saved.');
    }

    protected function validateNewsletter(Request $request): array
    {
        // Sanitize image field if empty string or string 'null' was sent via FormData
        if ($request->input('image') === 'null' || $request->input('image') === 'undefined' || $request->input('image') === '') {
            $request->request->remove('image');
        }

        return $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'status' => ['required', 'in:draft,scheduled,send_now'],
            'scheduled_at' => ['nullable', 'date', 'required_if:status,scheduled'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
        ]);
    }

    protected function storeNewsletterImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return $request->file('image')->store('newsletters', 'public');
    }

    protected function storeTestimonialPhoto(Request $request): ?string
    {
        if (! $request->hasFile('photo')) {
            return null;
        }

        return $request->file('photo')->store('testimonials', 'public');
    }

    protected function deleteTestimonialPhoto(?string $path): void
    {
        if (! $path) {
            return;
        }

        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function newsletterShow(Newsletter $newsletter): Response
    {
        $newsletter->load('creator:id,name');

        return Inertia::render('Admin/Marketing/Newsletters/Show', [
            'newsletter' => $newsletter,
            'subscriber_count' => NewsletterSubscriber::where('status', 'subscribed')->count(),
            'current_user_email' => auth()->user()?->email,
            'mail_from' => [
                'name' => config('mail.from.name', config('app.name', 'Envoy Electricals')),
                'address' => config('mail.from.address', 'hello@example.com'),
            ],
        ]);
    }

    public function newsletterEdit(Newsletter $newsletter): Response
    {
        abort_if($newsletter->status === 'sent', 409, 'This newsletter was already sent and can no longer be edited.');

        return Inertia::render('Admin/Marketing/Newsletters/Form', [
            'newsletter' => $newsletter,
        ]);
    }

    public function newsletterUpdate(Request $request, Newsletter $newsletter)
    {
        abort_if($newsletter->status === 'sent', 409, 'This newsletter was already sent and can no longer be edited.');

        $data = $this->validateNewsletter($request);

        $updateData = [
            'subject' => $data['subject'],
            'content' => $data['content'],
            'status' => $data['status'] === 'send_now' ? 'draft' : $data['status'],
            'scheduled_at' => $data['status'] === 'scheduled' ? ($data['scheduled_at'] ?? null) : null,
        ];

        // Handle image removal
        if (! empty($data['remove_image']) && $newsletter->image_path) {
            $this->deleteNewsletterImage($newsletter->image_path);
            $updateData['image_path'] = null;
        }

        // Handle new image upload
        if ($request->hasFile('image')) {
            if ($newsletter->image_path) {
                $this->deleteNewsletterImage($newsletter->image_path);
            }
            $updateData['image_path'] = $this->storeNewsletterImage($request);
        }

        $newsletter->update($updateData);

        AuditLogger::log('updated', 'newsletter', $newsletter->id, "Updated newsletter: {$newsletter->subject}");

        if ($data['status'] === 'send_now' || $request->boolean('send_now')) {
            $subscriberCount = NewsletterSubscriber::where('status', 'subscribed')->count();
            if ($subscriberCount > 0) {
                $result = app(NewsletterService::class)->send($newsletter);
                $message = "Newsletter updated and sent to {$result['delivered']} of {$result['total']} subscriber(s).";
                AuditLogger::log('sent', 'newsletter', $newsletter->id, $message);

                return redirect()->route('admin.marketing.newsletters.show', $newsletter->id)->with('success', $message);
            } else {
                return redirect()->route('admin.marketing.newsletters.show', $newsletter->id)
                    ->with('warning', 'Newsletter updated as draft. Could not broadcast because there are 0 active subscribers yet.');
            }
        }

        return redirect()->route('admin.marketing.newsletters.show', $newsletter->id)->with('success', 'Newsletter updated.');
    }

    public function newsletterSendTest(Request $request, Newsletter $newsletter)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $recipientEmail = $data['email'];

        // Mock subscriber object for test email rendering
        $subscriber = new NewsletterSubscriber([
            'email' => $recipientEmail,
            'name' => $request->user()->name ?? 'Test Recipient',
            'token' => 'preview-test-token',
            'status' => 'subscribed',
        ]);

        try {
            Mail::to($recipientEmail)->send(new NewsletterMail($newsletter, $subscriber));
            AuditLogger::log('test_sent', 'newsletter', $newsletter->id, "Sent test newsletter to {$recipientEmail}");

            return redirect()->back()->with('success', "Test email sent successfully to {$recipientEmail}.");
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->with('error', "Failed to send test email: {$e->getMessage()}");
        }
    }

    protected function deleteNewsletterImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function newsletterSend(Newsletter $newsletter)
    {
        abort_if($newsletter->status === 'sent', 409, 'This newsletter was already sent. Use "Resend" to send again.');

        $subscriberCount = NewsletterSubscriber::where('status', 'subscribed')->count();

        if ($subscriberCount === 0) {
            return redirect()->route('admin.marketing.newsletters.show', $newsletter->id)
                ->with('error', 'Cannot send — there are no active subscribers yet. Add subscribers first.');
        }

        $result = app(NewsletterService::class)->send($newsletter);

        if ($result['delivered'] === 0) {
            $message = 'Delivery failed for all '.($result['failed']).' subscriber(s). Check the mail configuration and try again.';

            AuditLogger::log('send_failed', 'newsletter', $newsletter->id, $message);

            return redirect()->route('admin.marketing.newsletters.show', $newsletter->id)
                ->with('error', $message);
        }

        $message = "Newsletter sent to {$result['delivered']} of {$result['total']} subscriber(s).";

        if ($result['failed'] > 0) {
            $message .= " {$result['failed']} failed (".implode(', ', array_slice($result['failures'], 0, 5)).').';
        }

        AuditLogger::log('sent', 'newsletter', $newsletter->id, $message);

        return redirect()->route('admin.marketing.newsletters.show', $newsletter->id)
            ->with('success', $message);
    }

    public function newsletterResend(Newsletter $newsletter)
    {
        abort_unless($newsletter->status === 'sent', 409, 'Only sent newsletters can be resent.');

        $subscriberCount = NewsletterSubscriber::where('status', 'subscribed')->count();

        if ($subscriberCount === 0) {
            return redirect()->route('admin.marketing.newsletters.show', $newsletter->id)
                ->with('error', 'Cannot resend — there are no active subscribers yet. Add subscribers first.');
        }

        $result = app(NewsletterService::class)->send($newsletter);

        if ($result['delivered'] === 0) {
            $message = 'Delivery failed for all '.($result['failed']).' subscriber(s). Check the mail configuration and try again.';

            AuditLogger::log('resend_failed', 'newsletter', $newsletter->id, $message);

            return redirect()->route('admin.marketing.newsletters.show', $newsletter->id)
                ->with('error', $message);
        }

        $message = "Newsletter resent to {$result['delivered']} of {$result['total']} subscriber(s).";

        if ($result['failed'] > 0) {
            $message .= " {$result['failed']} failed (".implode(', ', array_slice($result['failures'], 0, 5)).').';
        }

        AuditLogger::log('resent', 'newsletter', $newsletter->id, $message);

        return redirect()->route('admin.marketing.newsletters.show', $newsletter->id)
            ->with('success', $message);
    }

    public function newsletterDestroy(Newsletter $newsletter)
    {
        $subject = $newsletter->subject;
        $newsletter->delete();

        AuditLogger::log('deleted', 'newsletter', $newsletter->id, "Removed newsletter: {$subject}");

        return redirect()->route('admin.marketing.newsletters.index')->with('success', 'Newsletter removed.');
    }

    // ---------- Testimonials ----------

    public function testimonials(Request $request): Response
    {
        $testimonials = Testimonial::query()
            ->with('feedback')
            ->when($request->search, fn ($q, $s) => $q->where('author_name', 'like', "%{$s}%")->orWhere('content', 'like', "%{$s}%"))
            ->when($request->published, fn ($q, $p) => $q->where('is_published', (bool) $p))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'all' => Testimonial::count(),
            'pending' => Testimonial::where('status', 'pending')->count(),
            'approved' => Testimonial::where('status', 'approved')->count(),
            'rejected' => Testimonial::where('status', 'rejected')->count(),
        ];

        return Inertia::render('Admin/Marketing/Testimonials/Index', [
            'testimonials' => $testimonials,
            'filters' => $request->only(['search', 'published', 'status']),
            'counts' => $counts,
        ]);
    }

    public function testimonialCreate(): Response
    {
        return Inertia::render('Admin/Marketing/Testimonials/Form', [
            'testimonial' => null,
            'feedbackOptions' => Feedback::where('status', 'approved')->latest()->take(50)->get(['id', 'customer_name', 'comment']),
        ]);
    }

    public function testimonialStore(Request $request)
    {
        $data = $request->validate([
            'feedback_id' => ['nullable', 'int', 'exists:feedback,id'],
            'author_name' => ['required', 'string', 'max:255'],
            'author_role' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'content' => ['required', 'string'],
            'rating' => ['nullable', 'int', 'min:1', 'max:5'],
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'admin_notes' => ['nullable', 'string'],
            'is_published' => ['boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $testimonial = Testimonial::create([
            ...$data,
            'status' => $data['status'] ?? 'approved',
            'is_published' => (bool) ($data['is_published'] ?? false),
            'photo_path' => $this->storeTestimonialPhoto($request),
        ]);

        AuditLogger::log('created', 'testimonial', $testimonial->id, "Created testimonial from {$testimonial->author_name}");

        return redirect()->route('admin.marketing.testimonials.index')->with('success', 'Testimonial created.');
    }

    public function testimonialEdit(Testimonial $testimonial): Response
    {
        return Inertia::render('Admin/Marketing/Testimonials/Form', [
            'testimonial' => $testimonial,
            'feedbackOptions' => Feedback::where('status', 'approved')->latest()->take(50)->get(['id', 'customer_name', 'comment']),
        ]);
    }

    public function testimonialUpdate(Request $request, Testimonial $testimonial)
    {
        $data = $request->validate([
            'feedback_id' => ['nullable', 'int', 'exists:feedback,id'],
            'author_name' => ['required', 'string', 'max:255'],
            'author_role' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'content' => ['required', 'string'],
            'rating' => ['nullable', 'int', 'min:1', 'max:5'],
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'admin_notes' => ['nullable', 'string'],
            'is_published' => ['boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'remove_photo' => ['boolean'],
        ]);

        $updateData = [
            ...$data,
            'is_published' => (bool) ($data['is_published'] ?? false),
        ];

        // Handle photo removal
        if (! empty($data['remove_photo']) && $testimonial->photo_path) {
            $this->deleteTestimonialPhoto($testimonial->photo_path);
            $updateData['photo_path'] = null;
        }

        // Handle new photo upload
        if ($request->hasFile('photo')) {
            if ($testimonial->photo_path) {
                $this->deleteTestimonialPhoto($testimonial->photo_path);
            }
            $updateData['photo_path'] = $this->storeTestimonialPhoto($request);
        }

        $testimonial->update($updateData);

        AuditLogger::log('updated', 'testimonial', $testimonial->id, "Updated testimonial from {$testimonial->author_name}");

        return redirect()->route('admin.marketing.testimonials.index')->with('success', 'Testimonial updated.');
    }

    public function testimonialToggle(Testimonial $testimonial)
    {
        $testimonial->update(['is_published' => ! $testimonial->is_published]);

        AuditLogger::log('updated', 'testimonial', $testimonial->id, ($testimonial->is_published ? 'Published' : 'Unpublished').' testimonial from '.$testimonial->author_name);

        return redirect()->back()->with('success', $testimonial->is_published ? 'Testimonial published.' : 'Testimonial hidden.');
    }

    public function testimonialApprove(Request $request, Testimonial $testimonial)
    {
        $testimonial->update([
            'status' => 'approved',
            'is_published' => true,
        ]);

        AuditLogger::log('updated', 'testimonial', $testimonial->id, "Approved and published testimonial from {$testimonial->author_name}");

        return redirect()->back()->with('success', "Testimonial from {$testimonial->author_name} approved and published.");
    }

    public function testimonialReject(Request $request, Testimonial $testimonial)
    {
        $testimonial->update([
            'status' => 'rejected',
            'is_published' => false,
        ]);

        AuditLogger::log('updated', 'testimonial', $testimonial->id, "Rejected testimonial from {$testimonial->author_name}");

        return redirect()->back()->with('success', "Testimonial from {$testimonial->author_name} marked as rejected.");
    }

    public function testimonialStatus(Request $request, Testimonial $testimonial)
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected'],
        ]);

        $update = ['status' => $data['status']];
        if ($data['status'] === 'approved') {
            $update['is_published'] = true;
        } elseif ($data['status'] === 'rejected') {
            $update['is_published'] = false;
        }

        $testimonial->update($update);

        AuditLogger::log('updated', 'testimonial', $testimonial->id, "Changed testimonial status to {$data['status']} for {$testimonial->author_name}");

        return redirect()->back()->with('success', "Testimonial marked as {$data['status']}.");
    }

    public function testimonialDestroy(Testimonial $testimonial)
    {
        $author = $testimonial->author_name;
        $testimonial->delete();

        AuditLogger::log('deleted', 'testimonial', $testimonial->id, "Removed testimonial from {$author}");

        return redirect()->route('admin.marketing.testimonials.index')->with('success', 'Testimonial removed.');
    }

    public function testimonialGenerateLink(Testimonial $testimonial)
    {
        if (! $testimonial->token) {
            $testimonial->update(['token' => Testimonial::generateToken()]);
        }

        return redirect()->back()->with('success', 'Link generated: '.$testimonial->getPublicSubmissionUrl());
    }

    public function testimonialRevokeLink(Testimonial $testimonial)
    {
        $testimonial->update(['token' => null]);

        return redirect()->back()->with('success', 'Submission link revoked.');
    }

    // ---------- Public Testimonial Submission ----------

    public function publicTestimonialForm(string $token): Response
    {
        $testimonial = Testimonial::where('token', $token)->firstOrFail();

        // Only allow if status is pending (not yet submitted) or if we want to allow updates
        if ($testimonial->status !== 'pending') {
            abort(403, 'This testimonial link has already been used or is no longer valid.');
        }

        return Inertia::render('Public/TestimonialSubmit', [
            'testimonial' => $testimonial->only(['id', 'token', 'author_name', 'author_role', 'content', 'rating', 'email', 'phone', 'photo_path']),
        ]);
    }

    public function publicTestimonialSubmit(Request $request, string $token)
    {
        $testimonial = Testimonial::where('token', $token)->firstOrFail();

        if ($testimonial->status !== 'pending') {
            return redirect()->back()->with('error', 'This testimonial link has already been used or is no longer valid.');
        }

        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:255'],
            'author_role' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'content' => ['required', 'string', 'min:10'],
            'rating' => ['required', 'int', 'min:1', 'max:5'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $updateData = [
            ...$data,
            'status' => 'pending', // Keep as pending for admin review
            'is_published' => false,
        ];

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $updateData['photo_path'] = $this->storeTestimonialPhoto($request);
        }

        $testimonial->update($updateData);

        AuditLogger::log('updated', 'testimonial', $testimonial->id, "Public testimonial submitted by {$testimonial->author_name}");

        return redirect()->route('public.testimonial.thankyou', $token)->with('success', 'Thank you for your testimonial! It will be reviewed before publishing.');
    }

    public function publicTestimonialThankyou(string $token): Response
    {
        $testimonial = Testimonial::where('token', $token)->firstOrFail();

        return Inertia::render('Public/TestimonialThankyou', [
            'testimonial' => $testimonial->only(['author_name']),
        ]);
    }

    // Simple public submission (no token required) - creates new testimonial
    public function publicTestimonialCreate(): Response
    {
        return Inertia::render('Public/TestimonialSubmit', [
            'testimonial' => null,
        ]);
    }

    public function publicTestimonialStore(Request $request)
    {
        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:255'],
            'author_role' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'content' => ['required', 'string', 'min:10'],
            'rating' => ['required', 'int', 'min:1', 'max:5'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $createData = [
            ...$data,
            'status' => 'pending',
            'is_published' => false,
        ];

        if ($request->hasFile('photo')) {
            $createData['photo_path'] = $this->storeTestimonialPhoto($request);
        }

        $testimonial = Testimonial::create($createData);

        AuditLogger::log('created', 'testimonial', $testimonial->id, "Public testimonial submitted by {$testimonial->author_name}");

        return redirect()->route('public.testimonial.thankyou.simple')->with('success', 'Thank you for your testimonial! It will be reviewed before publishing.');
    }

    public function publicTestimonialThankyouSimple(): Response
    {
        return Inertia::render('Public/TestimonialThankyou', [
            'testimonial' => null,
        ]);
    }

    // ---------- Feedback ----------

    public function feedback(Request $request): Response
    {
        $feedback = Feedback::query()
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('customer_name', 'like', "%{$s}%")->orWhere('customer_email', 'like', "%{$s}%")->orWhere('comment', 'like', "%{$s}%");
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Marketing/Feedback/Index', [
            'feedback' => $feedback,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function feedbackShow(Feedback $feedback): Response
    {
        $feedback->load('user:id,name');

        return Inertia::render('Admin/Marketing/Feedback/Show', [
            'feedback' => $feedback,
        ]);
    }

    public function feedbackRespond(Request $request, Feedback $feedback)
    {
        $data = $request->validate([
            'admin_response' => ['nullable', 'string'],
            'status' => ['required', 'in:new,reviewed,approved,rejected'],
        ]);

        $feedback->update($data);

        if ($data['status'] === 'approved' && ! $feedback->testimonial()->exists() && $feedback->comment) {
            Testimonial::create([
                'feedback_id' => $feedback->id,
                'author_name' => $feedback->customer_name ?: 'Envoy Electricals Customer',
                'author_role' => 'Customer',
                'content' => $feedback->comment,
                'rating' => $feedback->rating,
                'is_published' => true,
            ]);
        }

        AuditLogger::log('updated', 'feedback', $feedback->id, "Processed feedback #{$feedback->id} as {$data['status']}");

        return redirect()->back()->with('success', 'Feedback updated.');
    }

    public function feedbackDestroy(Feedback $feedback)
    {
        $feedback->delete();

        AuditLogger::log('deleted', 'feedback', $feedback->id, "Removed feedback #{$feedback->id}");

        return redirect()->route('admin.marketing.feedback.index')->with('success', 'Feedback removed.');
    }
}
