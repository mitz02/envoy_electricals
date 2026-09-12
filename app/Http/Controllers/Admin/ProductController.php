<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\AuditLogger;
use App\Services\InventoryService;
use App\Services\ReferenceGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function __construct(protected InventoryService $inventory) {}

    public function index(Request $request): \Inertia\Response
    {
        $products = Product::query()
            ->with(['category', 'subcategory', 'supplier'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")->orWhere('ref_id', 'like', "%{$s}%");
            }))
            ->when($request->category_id, fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->stock_status, function ($q, $s) {
                match ($s) {
                    'low' => $q->whereRaw('current_quantity <= reorder_level')->where('current_quantity', '>', 0),
                    'out' => $q->where('current_quantity', '<=', 0),
                    default => null,
                };
            })
            ->orderBy($request->sort ?? 'updated_at', $request->order ?? 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'categories' => ProductCategory::orderBy('name')->get(),
            'filters' => $request->only(['search', 'category_id', 'stock_status', 'sort', 'order']),
        ]);
    }

    public function create(): \Inertia\Response
    {
        return Inertia::render('Admin/Products/Form', [
            'categories' => ProductCategory::with('children')->orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'product' => null,
        ]);
    }

    public function edit(Product $product): \Inertia\Response
    {
        $product->load(['category', 'subcategory', 'supplier', 'images']);

        return Inertia::render('Admin/Products/Form', [
            'categories' => ProductCategory::with('children')->orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'product' => $product,
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100'] ,
            'category_id' => ['nullable', 'exists:product_categories,id'],
            'subcategory_id' => ['nullable', 'exists:product_categories,id'],
            'brand' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'specifications' => ['nullable', 'string'],
            'unit' => ['nullable', 'string', 'max:50'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
            'is_featured' => ['nullable', 'boolean'],
            'is_visible_online' => ['nullable', 'boolean'],
            'allow_online_purchase' => ['nullable', 'boolean'],
            'opening_quantity' => ['nullable', 'integer', 'min:0'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
            'featured_image_id' => ['nullable', 'integer'],
            'featured_new_index' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->ensureUniqueSku($data['sku'], null);

        $product = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $request) {
            $product = Product::create([
                'ref_id' => ReferenceGenerator::generate('product'),
                'sku' => strtoupper($data['sku']),
                'name' => $data['name'],
                'category_id' => $data['category_id'] ?? null,
                'subcategory_id' => $data['subcategory_id'] ?? null,
                'brand' => $data['brand'] ?? null,
                'description' => $data['description'] ?? null,
                'specifications' => $data['specifications'] ?? null,
                'unit' => $data['unit'] ?? 'pcs',
                'barcode' => $data['barcode'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'cost_price' => $data['cost_price'],
                'selling_price' => $data['selling_price'],
                'reorder_level' => $data['reorder_level'] ?? 0,
                'status' => $data['status'] ?? 'active',
                'is_featured' => $request->boolean('is_featured'),
                'is_visible_online' => $request->boolean('is_visible_online', true),
                'allow_online_purchase' => $request->boolean('allow_online_purchase'),
            ]);

            if (($data['opening_quantity'] ?? 0) > 0 && (float) $data['cost_price'] >= 0) {
                $this->inventory->setOpeningStock(
                    $product,
                    (int) $data['opening_quantity'],
                    (float) $data['cost_price'],
                    $request->user()->id,
                );
            }

            $createdIds = $this->storeImages($product, $request->file('images') ?: []);
            $this->syncFeatured(
                $product,
                $createdIds,
                isset($data['featured_image_id']) ? (int) $data['featured_image_id'] : null,
                isset($data['featured_new_index']) ? (int) $data['featured_new_index'] : null,
            );

            return $product;
        });

        AuditLogger::log('created', 'product', $product->id, "Created product {$product->sku} - {$product->name}", null, $product->toArray());

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function update(Request $request, Product $product): \Illuminate\Http\RedirectResponse
    {
        /** @var array $data */
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100'],
            'category_id' => ['nullable', 'exists:product_categories,id'],
            'subcategory_id' => ['nullable', 'exists:product_categories,id'],
            'brand' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'specifications' => ['nullable', 'string'],
            'unit' => ['nullable', 'string', 'max:50'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
            'is_featured' => ['nullable', 'boolean'],
            'is_visible_online' => ['nullable', 'boolean'],
            'allow_online_purchase' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
            'featured_image_id' => ['nullable', 'integer'],
            'featured_new_index' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->ensureUniqueSku($data['sku'], $product->id);

        $old = $product->toArray();
        $product->update([
            'sku' => strtoupper($data['sku']),
            'name' => $data['name'],
            'category_id' => $data['category_id'] ?? null,
            'subcategory_id' => $data['subcategory_id'] ?? null,
            'brand' => $data['brand'] ?? null,
            'description' => $data['description'] ?? null,
            'specifications' => $data['specifications'] ?? null,
            'unit' => $data['unit'] ?? 'pcs',
            'barcode' => $data['barcode'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'cost_price' => $data['cost_price'],
            'selling_price' => $data['selling_price'],
            'reorder_level' => $data['reorder_level'] ?? 0,
            'status' => $data['status'] ?? 'active',
            'is_featured' => $request->boolean('is_featured'),
            'is_visible_online' => $request->boolean('is_visible_online', true),
            'allow_online_purchase' => $request->boolean('allow_online_purchase'),
        ]);

        if (! empty($data['remove_images'])) {
            $removals = ProductImage::where('product_id', $product->id)
                ->whereIn('id', $data['remove_images'])
                ->get();

            foreach ($removals as $image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            }
        }

        $createdIds = $this->storeImages($product, $request->file('images') ?: []);
        $this->syncFeatured(
            $product,
            $createdIds,
            isset($data['featured_image_id']) ? (int) $data['featured_image_id'] : null,
            isset($data['featured_new_index']) ? (int) $data['featured_new_index'] : null,
        );

        AuditLogger::log('updated', 'product', $product->id, "Updated product {$product->sku} - {$product->name}", $old, $product->toArray());

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product, Request $request): \Illuminate\Http\RedirectResponse
    {
        $sku = $product->sku;
        $name = $product->name;
        $product->delete();
        AuditLogger::log('deleted', 'product', $product->id, "Deleted product {$sku} - {$name}");

        return back()->with('success', 'Product deleted.');
    }

    public function stockMovements(Request $request): \Inertia\Response
    {
        $movements = StockMovement::query()
            ->with(['product', 'user'])
            ->when($request->product_id, fn ($q, $p) => $q->where('product_id', $p))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->from, fn ($q, $d) => $q->whereDate('movement_date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('movement_date', '<=', $d))
            ->latest('movement_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Inventory/Movements', [
            'movements' => $movements,
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku']),
            'filters' => $request->only(['product_id', 'type', 'from', 'to']),
            'types' => [
                StockMovement::TYPE_OPENING,
                StockMovement::TYPE_PURCHASE,
                StockMovement::TYPE_SALE,
                StockMovement::TYPE_PROJECT_ISSUE,
                StockMovement::TYPE_PROJECT_RETURN,
                StockMovement::TYPE_DAMAGE,
                StockMovement::TYPE_RETURN,
                StockMovement::TYPE_ADJUSTMENT,
            ],
        ]);
    }

    public function adjustStock(Request $request, Product $product): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'quantity_change' => ['required', 'integer', 'not_in:0'],
            'type' => ['required', 'in:damage,adjustment,return'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $change = (int) $data['quantity_change'];

        if ($change > 0) {
            $this->inventory->inbound(
                $product,
                $change,
                $data['type'] === 'return' ? StockMovement::TYPE_RETURN : StockMovement::TYPE_ADJUSTMENT,
                $product->sku . '|ADJ',
                reason: $data['reason'],
                documentType: 'adjustment',
                documentId: $product->id,
                userId: $request->user()->id,
            );
        } else {
            $this->inventory->outbound(
                $product,
                abs($change),
                $data['type'] === 'damage' ? StockMovement::TYPE_DAMAGE : StockMovement::TYPE_ADJUSTMENT,
                $product->sku . '|ADJ',
                reason: $data['reason'],
                documentType: 'adjustment',
                documentId: $product->id,
                userId: $request->user()->id,
            );
        }

        AuditLogger::log('stock_adjustment', 'product', $product->id,
            "Stock adjusted by {$change} ({$data['type']}): {$data['reason']}");

        return back()->with('success', 'Stock adjusted.');
    }

    protected function ensureUniqueSku(string $sku, ?int $exceptId): void
    {
        $exists = Product::where('sku', strtoupper($sku))
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();

        if ($exists) {
            abort(422, 'The SKU is already in use.');
        }
    }

    /**
     * Persist newly uploaded product images and return their created ids in order.
     *
     * @return array<int, int>
     */
    protected function storeImages(Product $product, array $files): array
    {
        $createdIds = [];
        $baseOrder = (int) $product->images()->count();

        foreach ($files as $i => $file) {
            $image = ProductImage::create([
                'product_id' => $product->id,
                'path' => $file->store('product-images', 'public'),
                'media_id' => null,
                'alt' => $product->name,
                'is_featured' => false,
                'sort_order' => $baseOrder + $i,
            ]);
            $createdIds[] = $image->id;
        }

        return $createdIds;
    }

    /**
     * Ensure exactly one image is marked as the cover (featured) image.
     *
     * @param  array<int, int>  $createdIds
     */
    protected function syncFeatured(Product $product, array $createdIds, ?int $featuredExistingId, ?int $featuredNewIndex): void
    {
        if ($product->images()->count() === 0) {
            return;
        }

        $featuredId = null;

        if ($featuredExistingId !== null && $product->images()->whereKey($featuredExistingId)->exists()) {
            $featuredId = $featuredExistingId;
        } elseif ($featuredNewIndex !== null && isset($createdIds[$featuredNewIndex])) {
            $featuredId = $createdIds[$featuredNewIndex];
        } else {
            $featuredId = (int) $product->images()->orderBy('sort_order')->orderBy('id')->value('id');
        }

        $product->images()->update(['is_featured' => false]);
        ProductImage::whereKey($featuredId)->update(['is_featured' => true]);
    }
}