<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        $categories = ProductCategory::orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug']);

        $categoryCounts = Product::query()
            ->whereNotNull('category_id')
            ->selectRaw('category_id, count(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $subcategoryCounts = Product::query()
            ->whereNotNull('subcategory_id')
            ->selectRaw('subcategory_id, count(*) as total')
            ->groupBy('subcategory_id')
            ->pluck('total', 'subcategory_id');

        $calculatorSettings = Setting::where('group', 'calculator')->pluck('value', 'key');

        return Inertia::render('Admin/Settings', [
            'categories' => $categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'product_count' => (int) ($categoryCounts[$c->id] ?? 0) + (int) ($subcategoryCounts[$c->id] ?? 0),
            ])->values(),
            'calculatorSettings' => $calculatorSettings,
        ]);
    }

    public function storeCategory(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category = DB::transaction(function () use ($data) {
            return ProductCategory::create([
                'name' => trim($data['name']),
                'slug' => $this->uniqueSlug($data['name']),
                'sort_order' => (int) ProductCategory::max('sort_order') + 1,
            ]);
        });

        AuditLogger::log('created', 'product_category', $category->id, "Created category {$category->name}");

        if ($request->wantsJson()) {
            return response()->json(['category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'product_count' => 0,
            ]]);
        }

        return redirect()->back()->with('success', "Category \"{$category->name}\" added.");
    }

    public function storeCalculator(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'panel_wattage' => ['required', 'integer', 'min:1'],
            'panel_efficiency' => ['required', 'numeric', 'min:0.01', 'max:1'],
            'inverter_safety_factor' => ['required', 'numeric', 'min:0.01'],
            'battery_capacities' => ['required', 'string', 'max:500'],
            'inverter_sizes' => ['required', 'string', 'max:500'],
            'price_per_panel' => ['required', 'numeric', 'min:0'],
            'price_per_kwh_daily' => ['required', 'numeric', 'min:0'],
            // Detailed pricing settings
            'inverter_prices' => ['nullable', 'array'],
            'inverter_prices.*' => ['nullable', 'numeric', 'min:0'],
            'battery_prices' => ['nullable', 'array'],
            'battery_prices.*' => ['nullable', 'numeric', 'min:0'],
            'installation_costs' => ['nullable', 'array'],
            'installation_costs.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                Setting::updateOrCreate(
                    ['key' => 'calculator.'.$key, 'group' => 'calculator'],
                    ['value' => json_encode($value)]
                );
            } else {
                Setting::updateOrCreate(
                    ['key' => 'calculator.'.$key, 'group' => 'calculator'],
                    ['value' => (string) $value]
                );
            }
        }

        // Clear cache so changes take effect immediately
        cache()->forget('settings.calculator');

        AuditLogger::log('updated', 'settings', 0, 'Updated calculator system parameters');

        return redirect()->back()->with('success', 'Calculator settings saved.');
    }

    public function destroyCategory(ProductCategory $category): RedirectResponse
    {
        DB::transaction(function () use ($category) {
            $ids = collect([$category->id])->merge($category->children()->pluck('id'))->all();

            Product::whereIn('category_id', $ids)->update(['category_id' => null]);
            Product::whereIn('subcategory_id', $ids)->update(['subcategory_id' => null]);

            ProductCategory::whereIn('id', $ids)->delete();
        });

        AuditLogger::log('deleted', 'product_category', $category->id, "Deleted category {$category->name}");

        return redirect()->back()->with('success', "Category \"{$category->name}\" deleted. Products were left uncategorised.");
    }

    protected function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name) !== '' ? Str::slug($name) : Str::lower(Str::random(6));

        if (! ProductCategory::where('slug', $slug)->exists()) {
            return $slug;
        }

        $base = $slug;
        $i = 2;
        while (ProductCategory::where('slug', $base.'-'.$i)->exists()) {
            $i++;
        }

        return $base.'-'.$i;
    }
}
