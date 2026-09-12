<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Feedback;
use App\Models\Media;
use App\Models\NewsletterSubscriber;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Models\SolarCalculation;
use App\Models\SolarPackage;
use App\Models\Trainee;
use App\Models\Training;
use App\Services\AcademyService;
use App\Services\AuditLogger;
use App\Services\PaystackService;
use App\Services\PaymentService;
use App\Services\ReferenceGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use RuntimeException;

class StorefrontController extends Controller
{
    public function shop(Request $request): \Inertia\Response
    {
        $active = $request->only(['search', 'category', 'min_price', 'max_price', 'availability', 'sort']);

        $products = Product::query()
            ->where('is_visible_online', true)
            ->with(['images', 'category'])
            ->when($active['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%")))
            ->when($active['category'] ?? null, fn ($q, $c) => $q->where('category_id', $c))
            ->when(($active['min_price'] ?? '') !== '', fn ($q) => $q->where('selling_price', '>=', (float) $active['min_price']))
            ->when(($active['max_price'] ?? '') !== '', fn ($q) => $q->where('selling_price', '<=', (float) $active['max_price']))
            ->when($active['availability'] ?? null, fn ($q, $a) => $q->where('current_quantity', $a === 'in_stock' ? '>' : '<=', 0))
            ->when(($active['sort'] ?? null) === 'price_asc', fn ($q) => $q->orderBy('selling_price'))
            ->when(($active['sort'] ?? null) === 'price_desc', fn ($q) => $q->orderByDesc('selling_price'))
            ->when(in_array($active['sort'] ?? null, ['newest']), fn ($q) => $q->orderByDesc('created_at'))
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $onlineCounts = Product::where('is_visible_online', true)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, count(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $categories = ProductCategory::orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'count' => $onlineCounts[$c->id] ?? 0,
            ])
            ->filter(fn ($c) => $c['count'] > 0)
            ->values();

        $priceBounds = Product::where('is_visible_online', true)
            ->selectRaw('min(selling_price) as min_price, max(selling_price) as max_price')
            ->first();

        return Inertia::render('Shop/Index', [
            'products' => $products,
            'filters' => array_merge([
                'search' => '',
                'category' => '',
                'min_price' => '',
                'max_price' => '',
                'availability' => '',
                'sort' => '',
            ], $active),
            'categories' => $categories,
            'priceBounds' => [
                'min' => (float) ($priceBounds->min_price ?? 0),
                'max' => (float) ($priceBounds->max_price ?? 0),
            ],
            'totalProducts' => Product::where('is_visible_online', true)->count(),
        ]);
    }

    public function product(Product $product): \Inertia\Response
    {
        abort_unless($product->is_visible_online, 404);

        return Inertia::render('Shop/ProductDetail', [
            'product' => $product->load(['images', 'category']),
            'related' => Product::query()
                ->where('is_visible_online', true)
                ->where('id', '!=', $product->id)
                ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
                ->take(4)
                ->get(),
        ]);
    }

    public function packages(): \Inertia\Response
    {
        return Inertia::render('Packages/Index', [
            'packages' => SolarPackage::query()
                ->where('is_visible_online', true)
                ->with('items')
                ->orderBy('package_price')
                ->get(),
        ]);
    }

    public function calculator(): \Inertia\Response
    {
        return Inertia::render('Calculator', [
            'packages' => SolarPackage::query()
                ->where('is_visible_online', true)
                ->where('availability', 'available')
                ->orderBy('package_price')
                ->get(['id', 'name', 'package_price', 'installation_cost', 'estimated_load_capacity', 'inverter_capacity', 'warranty']),
        ]);
    }

    public function calculatorStore(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'appliances' => ['nullable', 'array'],
            'total_connected_load' => ['nullable', 'numeric', 'min:0'],
            'daily_consumption_kwh' => ['nullable', 'numeric', 'min:0'],
            'peak_load_kw' => ['nullable', 'numeric', 'min:0'],
            'recommended_inverter' => ['nullable', 'string', 'max:255'],
            'recommended_panels' => ['nullable', 'integer', 'min:0'],
            'recommended_battery' => ['nullable', 'string', 'max:255'],
            'recommended_package_id' => ['nullable', 'exists:solar_packages,id'],
            'estimated_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $calc = SolarCalculation::create([
            'ref_id' => ReferenceGenerator::generate('solar_calculation'),
            'appliances_json' => $validated['appliances'] ?? null,
            'total_connected_load' => $validated['total_connected_load'] ?? 0,
            'daily_consumption_kwh' => $validated['daily_consumption_kwh'] ?? 0,
            'peak_load_kw' => $validated['peak_load_kw'] ?? 0,
            'recommended_inverter' => $validated['recommended_inverter'] ?? null,
            'recommended_panels' => $validated['recommended_panels'] ?? null,
            'recommended_battery' => $validated['recommended_battery'] ?? null,
            'recommended_package_id' => $validated['recommended_package_id'] ?? null,
            'estimated_price' => $validated['estimated_price'] ?? null,
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'customer_email' => $validated['customer_email'] ?? null,
            'location' => $validated['location'] ?? null,
            'lead_status' => 'new',
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLogger::log('created', 'solar_calculation', $calc->id, "New solar lead {$calc->ref_id} via public calculator");

        return redirect()->route('calculator')->with('success', 'Design saved! Our team will call you within 24 hours to confirm your quote. Reference: '.$calc->ref_id);
    }

    public function projects(): \Inertia\Response
    {
        return Inertia::render('Projects/Index', [
            'projects' => Project::query()
                ->where('published', true)
                ->with('media')
                ->orderByDesc('completion_date')
                ->orderByDesc('created_at')
                ->get(['id', 'ref_id', 'name', 'description', 'location', 'contract_value', 'status', 'completion_date', 'expected_completion_date', 'published']),
        ]);
    }

    public function training(): \Inertia\Response
    {
        $trainings = Training::active()
            ->withCount(['enrollments as enrolled_count' => fn ($q) => $q->where('status', '!=', Enrollment::STATUS_WITHDRAWN)])
            ->with('weeks.lessons')
            ->latest()
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'ref_id' => $t->ref_id,
                'title' => $t->title,
                'description' => $t->description,
                'image_path' => $t->image_path,
                'start_date' => $t->start_date?->toDateString(),
                'prerequisites' => $t->prerequisites,
                'objectives' => $t->objectives,
                'learning_outcomes' => $t->learning_outcomes,
                'curriculum' => $t->curriculum,
                'level' => $t->level,
                'duration_weeks' => $t->duration_weeks,
                'price' => $t->price,
                'capacity' => $t->capacity,
                'enrolled_count' => (int) $t->enrolled_count,
                'certificate_eligible' => $t->certificate_eligible,
                'is_featured' => $t->is_featured,
                'weeks' => $t->weeks->map(fn ($w) => [
                    'id' => $w->id,
                    'week_number' => $w->week_number,
                    'title' => $w->title,
                    'summary' => $w->summary,
                    'lessons' => $w->lessons->map(fn ($l) => [
                        'id' => $l->id,
                        'title' => $l->title,
                        'description' => $l->description,
                        'objectives' => $l->objectives,
                        'duration_minutes' => $l->duration_minutes,
                    ])->values(),
                ])->values(),
            ]);

        return Inertia::render('Training/Index', [
            'trainings' => $trainings,
        ]);
    }

    public function trainingShow(Training $training): \Inertia\Response
    {
        abort_unless($training->is_active, 404);

        $training->load(['creator', 'weeks.lessons']);

        $enrolledCount = $training->enrollments()
            ->where('status', '!=', Enrollment::STATUS_WITHDRAWN)
            ->count();

        return Inertia::render('Training/Show', [
            'training' => [
                'id' => $training->id,
                'ref_id' => $training->ref_id,
                'title' => $training->title,
                'description' => $training->description,
                'image_path' => $training->image_path,
                'start_date' => $training->start_date?->toDateString(),
                'prerequisites' => $training->prerequisites,
                'objectives' => $training->objectives,
                'learning_outcomes' => $training->learning_outcomes,
                'curriculum' => $training->curriculum,
                'level' => $training->level,
                'duration_weeks' => $training->duration_weeks,
                'price' => $training->price,
                'capacity' => $training->capacity,
                'enrolled_count' => $enrolledCount,
                'certificate_eligible' => $training->certificate_eligible,
                'is_featured' => $training->is_featured,
                'creator' => $training->creator ? [
                    'name' => $training->creator->name,
                ] : null,
                'weeks' => $training->weeks->map(fn ($w) => [
                    'id' => $w->id,
                    'week_number' => $w->week_number,
                    'title' => $w->title,
                    'summary' => $w->summary,
                    'lessons' => $w->lessons->map(fn ($l) => [
                        'id' => $l->id,
                        'title' => $l->title,
                        'description' => $l->description,
                        'objectives' => $l->objectives,
                        'duration_minutes' => $l->duration_minutes,
                    ])->values(),
                ])->values(),
            ],
            'paystackConfigured' => app(PaystackService::class)->isConfigured(),
            'bankDetails' => collect(\App\Models\Setting::where('group', 'bank')->pluck('value', 'key'))
                ->mapWithKeys(fn ($value, $key) => [str_replace('bank.', '', $key) => $value]),
            'whatsappNumber' => preg_replace('/\D/', '', (string) \App\Models\Setting::where('key', 'business.phone')->value('value')),
        ]);
    }

    public function trainingPaystack(Request $request, Training $training): JsonResponse
    {
        $paystack = app(PaystackService::class);

        if (! $paystack->isConfigured()) {
            return response()->json(['message' => 'Paystack is not configured on this server.'], 503);
        }

        abort_unless($training->is_active, 404);

        if ($training->price <= 0) {
            return response()->json(['message' => 'This training program is free. No payment required.'], 422);
        }

        $capacity = $training->capacity;
        if ($capacity !== null && $training->enrolled_count >= (int) $capacity) {
            return response()->json(['message' => 'This training program has reached its capacity.'], 422);
        }

        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Please log in to enroll.'], 401);
        }

        $trainee = $user->trainee;

        if (! $trainee) {
            return response()->json(['message' => 'Complete your trainee profile before enrolling.'], 422);
        }

        // Check if already enrolled
        $existing = Enrollment::where('trainee_id', $trainee->id)
            ->where('training_id', $training->id)
            ->exists();

        if ($existing) {
            return response()->json(['message' => 'You are already enrolled in this program.'], 422);
        }

        // Check if there's already a pending payment for this enrollment
        $existingPayment = \App\Models\Payment::where('document_type', 'enrollment')
            ->where('document_id', $training->id)
            ->where(function ($q) use ($trainee) {
                $q->where('trainee_id', $trainee->id)->orWhere('customer_id', $trainee->id);
            })
            ->where('gateway', \App\Models\Payment::GATEWAY_PAYSTACK)
            ->where('status', \App\Models\Payment::STATUS_PENDING)
            ->latest('id')
            ->first();

        $payment = $existingPayment ?? PaymentService::recordPayment(
            type: 'payment_in',
            amount: $training->price,
            paymentMethod: 'paystack',
            documentType: 'enrollment',
            documentId: $training->id,
            customerId: null,
            traineeId: $trainee->id,
            supplierId: null,
            reference: null,
            userId: $user->id,
            gateway: \App\Models\Payment::GATEWAY_PAYSTACK,
            status: \App\Models\Payment::STATUS_PENDING,
        );

        // An already-initialized reference cannot be re-initialized with Paystack
        // (duplicate_reference). Give this attempt a fresh reference so retries work.
        if ($existingPayment && $existingPayment->gateway_reference) {
            $payment->forceFill(['ref_id' => ReferenceGenerator::generate('payment')])->save();
        }

        try {
            $response = $paystack->initialize(
                reference: $payment->ref_id,
                amount: $training->price,
                email: $user->email ?? $trainee->email,
                callbackUrl: route('training.payment.callback', $training),
                metadata: [
                    'payment_ref' => $payment->ref_id,
                    'training_id' => $training->id,
                    'training_ref' => $training->ref_id,
                    'trainee_id' => $trainee->id,
                    'trainee_name' => $trainee->name,
                ],
            );
        } catch (\Throwable $e) {
            $detail = $e->getMessage();
            if (method_exists($e, 'response') && $e->response) {
                $json = $e->response->json();
                $detail .= ' :: ' . ($json['message'] ?? $e->response->body());
            }

            \Illuminate\Support\Facades\Log::error('Paystack initialize failed for training: ' . $detail, [
                'payment' => $payment->ref_id,
                'training' => $training->id,
            ]);

            return response()->json(['message' => 'Could not reach Paystack. Please try again.'], 502);
        }

        if (empty($response['status']) || empty($response['data']['authorization_url'] ?? null)) {
            \Illuminate\Support\Facades\Log::error('Paystack initialize returned invalid response for training.', ['response' => $response]);

            return response()->json(['message' => 'Paystack did not return a payment link.'], 502);
        }

        $payment->update(['gateway_reference' => $payment->ref_id]);

        return response()->json([
            'authorization_url' => $response['data']['authorization_url'],
            'reference' => $payment->ref_id,
        ]);
    }

    public function trainingPaymentCallback(Request $request, Training $training): \Symfony\Component\HttpFoundation\Response
    {
        $reference = $request->query('reference');
        $paystack = app(PaystackService::class);

        if (! $paystack->isConfigured()) {
            return redirect()->route('training.show', $training)
                ->with('error', 'Paystack is not configured on this server.');
        }

        if (! $reference) {
            return redirect()->route('training.show', $training)
                ->with('error', 'No payment reference provided.');
        }

        try {
            $verification = $paystack->verify($reference);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Paystack verification failed in callback: ' . $e->getMessage(), ['reference' => $reference]);

            return redirect()->route('training.show', $training)
                ->with('error', 'Could not verify payment. Please contact support.');
        }

        $success = $paystack->isSuccessfulVerification($verification);

        if ($success) {
            $this->applyTrainingPayment($training, $reference);

            return redirect()->route('portal.dashboard')
                ->with('success', "Payment verified! You've been enrolled in {$training->title}.");
        } else {
            return redirect()->route('training.show', $training)
                ->with('error', 'Payment verification failed. Please try again or contact support.');
        }
    }

    /**
     * Apply a verified Paystack payment to a training enrollment.
     *
     * Guarantees the enrollment is created even if the Paystack webhook was
     * not delivered (e.g. local development or a missed webhook call). Safe to
     * run repeatedly: it is idempotent for the enrollment and the payment.
     */
    protected function applyTrainingPayment(Training $training, string $reference): void
    {
        $payment = Payment::where('gateway', Payment::GATEWAY_PAYSTACK)
            ->where(function ($q) use ($reference) {
                $q->where('gateway_reference', $reference)->orWhere('reference', $reference);
            })
            ->first();

        $trainee = null;
        $userId = null;

        if ($payment) {
            $trainee = $payment->trainee_id ? Trainee::find($payment->trainee_id) : ($payment->customer_id ? Trainee::find($payment->customer_id) : null);
            $userId = $payment->created_by;
        }

        // Fallback when no payment record exists locally (rare): use the current
        // authenticated trainee and record the successful payment now.
        if (! $trainee) {
            $user = request()->user();
            $trainee = $user?->trainee;
            $userId = $user?->id;
        }

        if ($training && $trainee) {
            $this->enrollIfNotEnrolled($training, $trainee, $userId, $reference);
        }

        if ($payment && $payment->status !== Payment::STATUS_SUCCESS) {
            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'paid_at' => now(),
            ]);
        } elseif (! $payment && $training && $trainee) {
            PaymentService::recordPayment(
                type: 'payment_in',
                amount: $training->price,
                paymentMethod: 'paystack',
                documentType: 'enrollment',
                documentId: $training->id,
                customerId: null,
                traineeId: $trainee->id,
                supplierId: null,
                reference: $reference,
                userId: $userId,
                gateway: Payment::GATEWAY_PAYSTACK,
                gatewayReference: $reference,
                status: Payment::STATUS_SUCCESS,
                remarks: "Verified via Paystack reference {$reference}",
            );
        }
    }

    protected function enrollIfNotEnrolled(Training $training, Trainee $trainee, ?int $userId, string $reference): void
    {
        $exists = Enrollment::where('trainee_id', $trainee->id)
            ->where('training_id', $training->id)
            ->exists();

        if ($exists) {
            return;
        }

        try {
            app(AcademyService::class)->enroll($trainee, $training->id, $userId);
            AuditLogger::log('created', 'enrollment', 0, "Auto-enrolled {$trainee->name} in {$training->title} after Paystack verification {$reference}", userId: $userId);
        } catch (RuntimeException $e) {
            Log::error('Failed to enroll after Paystack verification.', ['reference' => $reference, 'error' => $e->getMessage()]);
        }
    }

    public function packageShow(SolarPackage $solarPackage): \Inertia\Response
    {
        abort_unless($solarPackage->is_visible_online, 404);

        $solarPackage->load('items');

        return Inertia::render('Packages/Show', [
            'package' => $solarPackage,
            'image' => $solarPackage->featured_image_media_id
                ? Media::find($solarPackage->featured_image_media_id)
                : null,
            'paystackConfigured' => app(PaystackService::class)->isConfigured(),
            'bankDetails' => \App\Models\Setting::where('group', 'bank')->pluck('value', 'key'),
        ]);
    }

    public function packagePaystack(Request $request, SolarPackage $solarPackage): JsonResponse
    {
        $paystack = app(PaystackService::class);

        if (! $paystack->isConfigured()) {
            return response()->json(['message' => 'Paystack is not configured on this server.'], 503);
        }

        abort_unless($solarPackage->is_visible_online, 404);

        $totalAmount = $solarPackage->package_price + ($solarPackage->installation_cost ?? 0);

        if ($totalAmount <= 0) {
            return response()->json(['message' => 'This package is free. No payment required.'], 422);
        }

        if ($solarPackage->availability !== 'available') {
            return response()->json(['message' => 'This package is currently unavailable.'], 422);
        }

        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Please log in to purchase.'], 401);
        }

        $customer = $user->customer;

        if (! $customer) {
            return response()->json(['message' => 'Complete your customer profile before purchasing.'], 422);
        }

        // Check if there's already a pending payment for this package
        $existingPayment = \App\Models\Payment::where('document_type', 'solar_package')
            ->where('document_id', $solarPackage->id)
            ->where('customer_id', $customer->id)
            ->where('gateway', \App\Models\Payment::GATEWAY_PAYSTACK)
            ->where('status', \App\Models\Payment::STATUS_PENDING)
            ->latest('id')
            ->first();

        $payment = $existingPayment ?? PaymentService::recordPayment(
            type: 'payment_in',
            amount: $totalAmount,
            paymentMethod: 'paystack',
            documentType: 'solar_package',
            documentId: $solarPackage->id,
            customerId: $customer->id,
            supplierId: null,
            reference: null,
            userId: $user->id,
            gateway: \App\Models\Payment::GATEWAY_PAYSTACK,
            status: \App\Models\Payment::STATUS_PENDING,
        );

        try {
            $response = $paystack->initialize(
                reference: $payment->ref_id,
                amount: $totalAmount,
                email: $user->email ?? $customer->email,
                callbackUrl: route('packages.payment.callback', $solarPackage),
                metadata: [
                    'payment_ref' => $payment->ref_id,
                    'package_id' => $solarPackage->id,
                    'package_name' => $solarPackage->name,
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                ],
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Paystack initialize failed for package: ' . $e->getMessage(), [
                'payment' => $payment->ref_id,
                'package' => $solarPackage->id,
            ]);

            return response()->json(['message' => 'Could not reach Paystack. Please try again.'], 502);
        }

        if (empty($response['status']) || empty($response['data']['authorization_url'] ?? null)) {
            \Illuminate\Support\Facades\Log::error('Paystack initialize returned invalid response for package.', ['response' => $response]);

            return response()->json(['message' => 'Paystack did not return a payment link.'], 502);
        }

        $payment->update(['gateway_reference' => $payment->ref_id]);

        return response()->json([
            'authorization_url' => $response['data']['authorization_url'],
            'reference' => $payment->ref_id,
        ]);
    }

    public function packagePaymentCallback(Request $request, SolarPackage $solarPackage): \Inertia\Response
    {
        $reference = $request->query('reference');
        $paystack = app(PaystackService::class);

        if (! $paystack->isConfigured()) {
            return redirect()->route('packages.show', $solarPackage)
                ->with('error', 'Paystack is not configured on this server.');
        }

        if (! $reference) {
            return redirect()->route('packages.show', $solarPackage)
                ->with('error', 'No payment reference provided.');
        }

        try {
            $verification = $paystack->verify($reference);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Paystack verification failed in callback: ' . $e->getMessage(), ['reference' => $reference]);

            return redirect()->route('packages.show', $solarPackage)
                ->with('error', 'Could not verify payment. Please contact support.');
        }

        $success = $paystack->isSuccessfulVerification($verification);

        if ($success) {
            return redirect()->route('orders.pay', ['order' => $reference])
                ->with('success', "Payment verified for {$solarPackage->name}!");
        } else {
            return redirect()->route('packages.show', $solarPackage)
                ->with('error', 'Payment verification failed. Please try again or contact support.');
        }
    }

    public function packageOffline(Request $request, SolarPackage $solarPackage): \Inertia\Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('portal.login')
                ->with('error', 'Please log in to make an offline payment.');
        }

        $customer = $user->customer;

        if (! $customer) {
            return redirect()->route('portal.profile.edit')
                ->with('error', 'Complete your customer profile before making a payment.');
        }

        abort_unless($solarPackage->is_visible_online, 404);

        if ($solarPackage->availability !== 'available') {
            return redirect()->route('packages.show', $solarPackage)
                ->with('error', 'This package is currently unavailable.');
        }

        $totalAmount = $solarPackage->package_price + ($solarPackage->installation_cost ?? 0);

        // Record offline payment intent
        $payment = \App\Services\PaymentService::recordPayment(
            type: 'payment_in',
            amount: $totalAmount,
            paymentMethod: 'bank_transfer',
            documentType: 'solar_package',
            documentId: $solarPackage->id,
            customerId: $customer->id,
            supplierId: null,
            reference: null,
            userId: $user->id,
            gateway: \App\Models\Payment::GATEWAY_LOCAL,
            status: \App\Models\Payment::STATUS_PENDING,
        );

        return redirect()->route('packages.show', $solarPackage)
            ->with('success', 'Offline payment initiated! Complete the bank transfer and we\'ll verify your payment.')
            ->with('showOfflineDetails', true);
    }

    public function feedback(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'experience' => ['nullable', 'string', 'max:2000'],
            'suggestion' => ['nullable', 'string', 'max:2000'],
        ]);

        // Handle callback requests (phone only, no email/comment)
        $comment = $validated['comment'] ?? null;
        if ($validated['phone'] && ! $validated['customer_email'] && ! $comment) {
            $comment = "Callback request: {$validated['phone']}";
        }

        Feedback::create([
            'user_id' => $request->user()?->id,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'] ?? null,
            'rating' => $validated['rating'] ?? null,
            'comment' => $comment,
            'experience' => $validated['experience'] ?? null,
            'suggestion' => $validated['suggestion'] ?? null,
            'status' => 'new',
        ]);

        AuditLogger::log('created', 'feedback', 0, 'New customer feedback via website');

        return back()->with('success', 'Thank you for your feedback!');
    }

    public function newsletter(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        NewsletterSubscriber::updateOrCreate(
            ['email' => mb_strtolower($validated['email'])],
            ['name' => $validated['name'] ?? null, 'status' => 'subscribed']
        );

        return back()->with('success', "You've been subscribed! Stay tuned for solar tips and offers.");
    }
}