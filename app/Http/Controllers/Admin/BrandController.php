<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function index(Request $request): Response
    {
        $brands = Brand::query()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->withCount('products')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Brands/Index', [
            'brands' => $brands,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('brands', 'name')],
        ]);

        Brand::create([
            ...$data,
            'slug' => $this->uniqueSlug($data['name']),
        ]);

        return back()->with('success', 'Brand added.');
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('brands', 'name')->ignore($brand->id)],
            'is_active' => ['boolean'],
        ]);

        $brand->update([
            ...$data,
            'slug' => $this->uniqueSlug($data['name'], $brand->id),
        ]);

        return back()->with('success', 'Brand updated.');
    }

    public function show(Brand $brand): Response
    {
        $brand->loadCount('products');
        $brand->load(['products' => fn ($q) => $q->latest()->take(10)]);

        return Inertia::render('Admin/Brands/Show', [
            'brand' => $brand,
        ]);
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        return back()->with('success', 'Brand deleted.');
    }

    public function quickCreate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('brands', 'name')],
        ]);

        $brand = Brand::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'is_active' => true,
        ]);

        return response()->json(['brand' => $brand]);
    }

    /**
     * Build a slug that is unique across the brands table, since the column is
     * indexed as unique and two brands may share a name-derived slug.
     */
    protected function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'brand';
        $slug = $base;
        $suffix = 1;

        while (Brand::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.++$suffix;
        }

        return $slug;
    }
}
