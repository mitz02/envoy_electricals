<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Media;
use App\Models\NewsletterSubscriber;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Models\SolarCalculation;
use App\Models\SolarPackage;
use App\Services\AuditLogger;
use App\Services\ReferenceGenerator;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

    public function packageShow(SolarPackage $solarPackage): \Inertia\Response
    {
        abort_unless($solarPackage->is_visible_online, 404);

        $solarPackage->load('items');

        return Inertia::render('Packages/Show', [
            'package' => $solarPackage,
            'image' => $solarPackage->featured_image_media_id
                ? Media::find($solarPackage->featured_image_media_id)
                : null,
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

    public function feedback(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'experience' => ['nullable', 'string', 'max:2000'],
            'suggestion' => ['nullable', 'string', 'max:2000'],
        ]);

        Feedback::create([
            'user_id' => $request->user()?->id,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'] ?? null,
            'rating' => $validated['rating'] ?? null,
            'comment' => $validated['comment'] ?? null,
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