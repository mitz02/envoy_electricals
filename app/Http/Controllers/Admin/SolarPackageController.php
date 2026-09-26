<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Product;
use App\Models\SolarPackage;
use App\Models\SolarPackageImage;
use App\Services\AuditLogger;
use App\Services\ReferenceGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SolarPackageController extends Controller
{
    public function index(Request $request): Response
    {
        $packages = SolarPackage::query()
            ->withCount('items')
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('ref_id', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            }))
            ->when($request->availability, fn ($q, $a) => $q->where('availability', $a))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total' => SolarPackage::count(),
            'featured' => SolarPackage::where('is_featured', true)->count(),
            'available' => SolarPackage::where('availability', 'available')->count(),
        ];

        return Inertia::render('Admin/SolarPackages/Index', [
            'packages' => $packages,
            'summary' => $summary,
            'filters' => $request->only(['search', 'availability']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/SolarPackages/Form', [
            'package' => null,
            'products' => $this->productOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'package_price' => ['required', 'numeric', 'min:0'],
            'installation_cost' => ['nullable', 'numeric', 'min:0'],
            'estimated_load_capacity' => ['nullable', 'string', 'max:255'],
            'inverter_capacity' => ['nullable', 'string', 'max:255'],
            'custom_inverter_capacity' => ['nullable', 'string', 'max:255'],
            'warranty' => ['nullable', 'string', 'max:255'],
            'is_featured' => ['boolean'],
            'availability' => ['required', 'in:available,unavailable'],
            'is_visible_online' => ['boolean'],
            'items' => ['array'],
            'items.*.product_id' => ['nullable', 'int', 'exists:products,id'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.specification' => ['nullable', 'string', 'max:255'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ]);

        $package = SolarPackage::create([
            'ref_id' => ReferenceGenerator::generate('solar_package'),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'package_price' => $data['package_price'],
            'installation_cost' => $data['installation_cost'] ?? 0,
            'estimated_load_capacity' => $data['estimated_load_capacity'] ?? null,
            'inverter_capacity' => $data['inverter_capacity'] ?? null,
            'custom_inverter_capacity' => $data['custom_inverter_capacity'] ?? null,
            'warranty' => $data['warranty'] ?? null,
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'availability' => $data['availability'],
            'is_visible_online' => (bool) ($data['is_visible_online'] ?? true),
            'components_json' => $this->buildComponents($data['items'] ?? []),
        ]);

        $this->syncItems($package, $data['items'] ?? []);

        // Handle image uploads
        $createdIds = $this->storeImages($package, $request->file('images') ?? []);

        AuditLogger::log('created', 'solar_package', $package->id, "Created solar package {$package->ref_id}: {$package->name}");

        return redirect()->route('admin.solar-packages.show', $package->id)->with('success', 'Solar package created.');
    }

    public function show(SolarPackage $package): Response
    {
        return Inertia::render('Admin/SolarPackages/Show', [
            'package' => $package->load('items.product', 'images.media'),
        ]);
    }

    public function edit(SolarPackage $package): Response
    {
        return Inertia::render('Admin/SolarPackages/Form', [
            'package' => $package->load('items', 'images.media'),
            'products' => $this->productOptions(),
        ]);
    }

    public function update(Request $request, SolarPackage $package)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'package_price' => ['required', 'numeric', 'min:0'],
            'installation_cost' => ['nullable', 'numeric', 'min:0'],
            'estimated_load_capacity' => ['nullable', 'string', 'max:255'],
            'inverter_capacity' => ['nullable', 'string', 'max:255'],
            'custom_inverter_capacity' => ['nullable', 'string', 'max:255'],
            'warranty' => ['nullable', 'string', 'max:255'],
            'is_featured' => ['boolean'],
            'availability' => ['required', 'in:available,unavailable'],
            'is_visible_online' => ['boolean'],
            'items' => ['array'],
            'items.*.product_id' => ['nullable', 'int', 'exists:products,id'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.specification' => ['nullable', 'string', 'max:255'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
        ]);

        $package->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'package_price' => $data['package_price'],
            'installation_cost' => $data['installation_cost'] ?? 0,
            'estimated_load_capacity' => $data['estimated_load_capacity'] ?? null,
            'inverter_capacity' => $data['inverter_capacity'] ?? null,
            'custom_inverter_capacity' => $data['custom_inverter_capacity'] ?? null,
            'warranty' => $data['warranty'] ?? null,
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'availability' => $data['availability'],
            'is_visible_online' => (bool) ($data['is_visible_online'] ?? true),
            'components_json' => $this->buildComponents($data['items'] ?? []),
        ]);

        $this->syncItems($package, $data['items'] ?? []);

        // Handle image removals
        if (! empty($data['remove_images'])) {
            $imagesToRemove = SolarPackageImage::whereIn('id', $data['remove_images'])
                ->where('solar_package_id', $package->id)
                ->get();

            foreach ($imagesToRemove as $image) {
                if ($image->media_id) {
                    $media = Media::find($image->media_id);
                    if ($media && Storage::disk('public')->exists($media->path)) {
                        Storage::disk('public')->delete($media->path);
                    }
                    $media?->delete();
                }
                $image->delete();
            }
        }

        // Handle new image uploads
        $createdIds = $this->storeImages($package, $request->file('images') ?? []);

        AuditLogger::log('updated', 'solar_package', $package->id, "Updated solar package {$package->ref_id}: {$package->name}");

        return redirect()->route('admin.solar-packages.show', $package->id)->with('success', 'Solar package updated.');
    }

    public function destroy(Request $request, SolarPackage $package)
    {
        $ref = $package->ref_id;
        $package->delete();

        AuditLogger::log('deleted', 'solar_package', $package->id, "Removed solar package {$ref}");

        return redirect()->route('admin.solar-packages.index')->with('success', 'Solar package removed.');
    }

    protected function syncItems(SolarPackage $package, array $items): void
    {
        $package->items()->delete();

        foreach ($items as $item) {
            $package->items()->create($item);
        }
    }

    protected function buildComponents(array $items): array
    {
        return array_values(array_filter($items, fn ($i) => $i['name'] ?? null));
    }

    /**
     * Persist newly uploaded solar package images.
     *
     * @return array<int, int>
     */
    protected function storeImages(SolarPackage $package, array $files): array
    {
        $createdIds = [];
        $baseOrder = (int) $package->images()->count();

        foreach ($files as $i => $file) {
            $media = Media::create([
                'name' => $file->getClientOriginalName(),
                'path' => $file->store('solar-packages', 'public'),
                'type' => 'image',
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'category' => 'solar_package',
                'uploaded_by' => auth()->id(),
            ]);

            $image = SolarPackageImage::create([
                'solar_package_id' => $package->id,
                'media_id' => $media->id,
                'sort_order' => $baseOrder + $i,
                'is_featured' => false,
            ]);
            $createdIds[] = $image->id;
        }

        return $createdIds;
    }

    protected function productOptions(): array
    {
        return Product::query()
            ->select('id', 'name', 'sku')
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku])
            ->toArray();
    }
}
