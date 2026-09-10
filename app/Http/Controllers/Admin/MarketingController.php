<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Newsletter;
use App\Models\NewsletterSubscriber;
use App\Models\Testimonial;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MarketingController extends Controller
{
    public function index(): \Inertia\Response
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

    public function subscribers(Request $request): \Inertia\Response
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

    public function newsletters(Request $request): \Inertia\Response
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

    public function newsletterCreate(): \Inertia\Response
    {
        return Inertia::render('Admin/Marketing/Newsletters/Form', [
            'newsletter' => null,
        ]);
    }

    public function newsletterStore(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'status' => ['required', 'in:draft,scheduled'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $newsletter = Newsletter::create([
            ...$data,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        AuditLogger::log('created', 'newsletter', $newsletter->id, "Created newsletter: {$newsletter->subject}");

        return redirect()->route('admin.marketing.newsletters.show', $newsletter->id)->with('success', 'Newsletter saved.');
    }

    public function newsletterShow(Newsletter $newsletter): \Inertia\Response
    {
        $newsletter->load('creator:id,name');

        return Inertia::render('Admin/Marketing/Newsletters/Show', [
            'newsletter' => $newsletter,
            'subscriber_count' => NewsletterSubscriber::where('status', 'subscribed')->count(),
        ]);
    }

    public function newsletterEdit(Newsletter $newsletter): \Inertia\Response
    {
        return Inertia::render('Admin/Marketing/Newsletters/Form', [
            'newsletter' => $newsletter,
        ]);
    }

    public function newsletterUpdate(Request $request, Newsletter $newsletter)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'status' => ['required', 'in:draft,scheduled'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $newsletter->update($data);

        AuditLogger::log('updated', 'newsletter', $newsletter->id, "Updated newsletter: {$newsletter->subject}");

        return redirect()->route('admin.marketing.newsletters.show', $newsletter->id)->with('success', 'Newsletter updated.');
    }

    public function newsletterSend(Newsletter $newsletter)
    {
        abort_if($newsletter->status === 'sent', 409, 'This newsletter was already sent.');

        $newsletter->update([
            'status' => 'sent',
            'sent_at' => now(),
            'scheduled_at' => null,
        ]);

        AuditLogger::log('sent', 'newsletter', $newsletter->id, "Marked newsletter as sent: {$newsletter->subject}");

        return redirect()->route('admin.marketing.newsletters.show', $newsletter->id)->with('success', 'Newsletter marked as sent to all subscribed recipients.');
    }

    public function newsletterDestroy(Newsletter $newsletter)
    {
        $subject = $newsletter->subject;
        $newsletter->delete();

        AuditLogger::log('deleted', 'newsletter', $newsletter->id, "Removed newsletter: {$subject}");

        return redirect()->route('admin.marketing.newsletters.index')->with('success', 'Newsletter removed.');
    }

    // ---------- Testimonials ----------

    public function testimonials(Request $request): \Inertia\Response
    {
        $testimonials = Testimonial::query()
            ->with('feedback')
            ->when($request->search, fn ($q, $s) => $q->where('author_name', 'like', "%{$s}%")->orWhere('content', 'like', "%{$s}%"))
            ->when($request->published, fn ($q, $p) => $q->where('is_published', (bool) $p))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Marketing/Testimonials/Index', [
            'testimonials' => $testimonials,
            'filters' => $request->only(['search', 'published']),
        ]);
    }

    public function testimonialCreate(): \Inertia\Response
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
            'content' => ['required', 'string'],
            'rating' => ['nullable', 'int', 'min:1', 'max:5'],
            'is_published' => ['boolean'],
        ]);

        $testimonial = Testimonial::create([
            ...$data,
            'is_published' => (bool) ($data['is_published'] ?? false),
        ]);

        AuditLogger::log('created', 'testimonial', $testimonial->id, "Created testimonial from {$testimonial->author_name}");

        return redirect()->route('admin.marketing.testimonials.index')->with('success', 'Testimonial created.');
    }

    public function testimonialEdit(Testimonial $testimonial): \Inertia\Response
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
            'content' => ['required', 'string'],
            'rating' => ['nullable', 'int', 'min:1', 'max:5'],
            'is_published' => ['boolean'],
        ]);

        $testimonial->update([
            ...$data,
            'is_published' => (bool) ($data['is_published'] ?? false),
        ]);

        AuditLogger::log('updated', 'testimonial', $testimonial->id, "Updated testimonial from {$testimonial->author_name}");

        return redirect()->route('admin.marketing.testimonials.index')->with('success', 'Testimonial updated.');
    }

    public function testimonialToggle(Testimonial $testimonial)
    {
        $testimonial->update(['is_published' => ! $testimonial->is_published]);

        AuditLogger::log('updated', 'testimonial', $testimonial->id, ($testimonial->is_published ? 'Published' : 'Unpublished') . ' testimonial from ' . $testimonial->author_name);

        return redirect()->back()->with('success', $testimonial->is_published ? 'Testimonial published.' : 'Testimonial hidden.');
    }

    public function testimonialDestroy(Testimonial $testimonial)
    {
        $author = $testimonial->author_name;
        $testimonial->delete();

        AuditLogger::log('deleted', 'testimonial', $testimonial->id, "Removed testimonial from {$author}");

        return redirect()->route('admin.marketing.testimonials.index')->with('success', 'Testimonial removed.');
    }

    // ---------- Feedback ----------

    public function feedback(Request $request): \Inertia\Response
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

    public function feedbackShow(Feedback $feedback): \Inertia\Response
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
                'author_name' => $feedback->customer_name ?: 'Envoy Electric Customer',
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