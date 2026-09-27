<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\InventoryService;
use App\Services\ReferenceGenerator;
use App\Support\StoreAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(protected InventoryService $inventory) {}

    /**
     * Resolve the target store for a write and confirm the actor may use it.
     *
     * Falls back to the selected branch, then the actor's first allowed branch,
     * then the system default — so a restricted user can never post stock into
     * a branch they have been locked out of.
     *
     * @throws ValidationException
     */
    protected function authorizeStore(Request $request, ?int $storeId, string $field = 'store_id'): int
    {
        $user = $request->user();
        $storeId ??= StoreAccess::selectedStoreId($user);
        $storeId ??= $user?->allowedStoreIds()[0] ?? null;

        if ($storeId === null) {
            $defaultId = (int) Store::where('is_default', true)->value('id');

            if ($defaultId && StoreAccess::canAccess($user, $defaultId)) {
                $storeId = $defaultId;
            }
        }

        if ($storeId === null || ! StoreAccess::canAccess($user, $storeId)) {
            throw ValidationException::withMessages([
                $field => 'You do not have access to that branch.',
            ]);
        }

        return $storeId;
    }

    /**
     * Reject a product the current user has no reachable branch for.
     *
     * Restricted users must not reach a product that lives only in branches
     * they cannot see, whether by guessing the URL or by posting an id.
     * Unrestricted users (the owner role) always pass.
     */
    protected function ensureProductVisible(?User $user, Product $product): void
    {
        $allowed = $user?->allowedStoreIds();

        if ($allowed === null) {
            return;
        }

        abort_unless(
            DB::table('product_store')
                ->where('product_id', $product->id)
                ->whereIn('store_id', $allowed)
                ->exists(),
            404
        );
    }

    /**
     * Product ids the current user can reach, or null when unrestricted.
     */
    protected function visibleProductIds(?User $user): ?array
    {
        $allowed = $user?->allowedStoreIds();

        if ($allowed === null) {
            return null;
        }

        return DB::table('product_store')
            ->whereIn('store_id', $allowed)
            ->distinct()
            ->pluck('product_id')
            ->all();
    }

    public function index(Request $request): Response
    {
        $allowed = $request->user()?->allowedStoreIds();
        $storeId = StoreAccess::selectedStoreId($request->user());

        $products = Product::query()
            ->with(['category', 'subcategory', 'brand', 'supplier', 'stores'])
            ->when($storeId, function ($q) use ($storeId) {
                $q->whereHas('stores', fn ($q) => $q->where('stores.id', $storeId));
            })
            // Restricted user with no single branch selected: only products
            // that actually exist in one of their own branches.
            ->when(! $storeId && $allowed !== null, function ($q) use ($allowed) {
                $q->whereHas('stores', fn ($q) => $q->whereIn('stores.id', $allowed));
            })
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")->orWhere('ref_id', 'like', "%{$s}%");
            }))
            ->when($request->category_id, fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->stock_status && $storeId, function ($q, $s) use ($storeId) {
                match ($s) {
                    'low' => $q->whereHas('stores', fn ($q) => $q->where('stores.id', $storeId)
                        ->whereRaw('product_store.current_quantity <= product_store.reorder_level')
                        ->where('product_store.current_quantity', '>', 0)),
                    'out' => $q->whereHas('stores', fn ($q) => $q->where('stores.id', $storeId)
                        ->where('product_store.current_quantity', '<=', 0)),
                    default => null,
                };
            })
            ->when($request->stock_status && ! $storeId, function ($q, $s) {
                match ($s) {
                    'low' => $q->whereRaw('current_quantity <= reorder_level')->where('current_quantity', '>', 0),
                    'out' => $q->where('current_quantity', '<=', 0),
                    default => null,
                };
            })
            ->orderBy($request->sort ?? 'updated_at', $request->order ?? 'desc')
            ->paginate(15)
            ->withQueryString();

        $products->getCollection()->transform(function ($product) use ($storeId, $allowed) {
            $visible = $storeId
                ? [$storeId]
                : ($allowed ?? $product->stores->pluck('id')->all());

            $visibleStores = $product->stores
                ->filter(fn ($store) => in_array($store->id, $visible, true));

            $pivots = $visibleStores->map(fn ($store) => $store->pivot);

            // Narrow the relation itself so branches outside the user's reach
            // are never serialized into the page payload.
            $product->setRelation('stores', $visibleStores->values());

            if ($pivots->isNotEmpty()) {
                $quantity = (int) $pivots->sum('current_quantity');
                $cost = (float) ($pivots->sum(fn ($p) => (int) $p->current_quantity * (float) $p->average_cost) / max($quantity, 1));

                $product->current_quantity = $quantity;
                $product->reorder_level = (int) $pivots->max('reorder_level');
                $product->average_cost = round($cost, 2);

                $price = $pivots->first(fn ($p) => $p->selling_price !== null);
                if ($price) {
                    $product->selling_price = $price->selling_price;
                }
            }

            return $product;
        });

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'categories' => ProductCategory::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'stores' => StoreAccess::activeQuery($request->user())->get(['id', 'name', 'code']),
            'filters' => $request->only(['search', 'category_id', 'stock_status', 'sort', 'order']),
            'selectedStoreId' => $storeId,
        ]);
    }

    /**
     * Product detail hub: current position, per-store breakdown and full stock history.
     */
    public function show(Request $request, Product $product): Response
    {
        $user = $request->user();
        $this->ensureProductVisible($user, $product);

        $allowed = $user?->allowedStoreIds();
        $storeId = StoreAccess::selectedStoreId($user);

        $product->load(['category', 'subcategory', 'brand', 'supplier', 'images']);

        $storeRows = DB::table('product_store')
            ->join('stores', 'stores.id', '=', 'product_store.store_id')
            ->where('product_store.product_id', $product->id)
            ->when($allowed !== null, fn ($q) => $q->whereIn('product_store.store_id', $allowed))
            ->orderByDesc('stores.is_default')
            ->orderBy('stores.name')
            ->get([
                'product_store.store_id',
                'stores.name as store_name',
                'stores.is_default',
                'product_store.current_quantity',
                'product_store.average_cost',
                'product_store.selling_price',
                'product_store.reorder_level',
            ])
            ->map(fn ($row) => [
                'store_id' => (int) $row->store_id,
                'store_name' => $row->store_name,
                'is_default' => (bool) $row->is_default,
                'current_quantity' => (int) $row->current_quantity,
                'average_cost' => (float) $row->average_cost,
                'stock_value' => round((int) $row->current_quantity * (float) $row->selling_price, 2),
                'reorder_level' => (int) $row->reorder_level,
            ]);

        $selectedStore = $storeId ? $storeRows->firstWhere('store_id', (int) $storeId) : null;

        if ($selectedStore) {
            $onHand = $selectedStore['current_quantity'];
            $averageCost = $selectedStore['average_cost'];
        } elseif ($allowed !== null) {
            // Restricted user with no branch picked: roll the totals up across
            // their own branches only, never the whole product's global stock.
            $onHand = (int) $storeRows->sum('current_quantity');
            $weighted = (float) $storeRows->sum(fn ($row) => $row['current_quantity'] * $row['selling_price']);
            $averageCost = $onHand > 0 ? round($weighted / $onHand, 2) : (float) $product->average_cost;
        } else {
            $onHand = (int) $product->current_quantity;
            $averageCost = (float) $product->average_cost;
        }

        // A restricted user's purchase/sale history is limited to the branches
        // they can reach, because the scope already hides the rest.
        $purchases = PurchaseItem::query()
            ->with(['purchase:id,ref_id,purchase_date,status,supplier_id', 'purchase.supplier:id,name'])
            ->where('product_id', $product->id)
            ->when($allowed !== null, fn ($q) => $q->whereHas('purchase'))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $movements = $product->movements()
            ->with(['user:id,name', 'store:id,name'])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->latest('movement_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Products/Show', [
            'product' => $product,
            'stores' => $storeRows,
            'selectedStoreId' => $storeId ? (int) $storeId : null,
            'summary' => [
                'on_hand' => $onHand,
                'average_cost' => round($averageCost, 2),
                'stock_value' => round($onHand * (float) $product->selling_price, 2),
                'reorder_level' => (int) $product->reorder_level,
                'selling_price' => (float) $product->selling_price,
                'total_purchased' => (int) PurchaseItem::where('product_id', $product->id)
                    ->when($allowed !== null, fn ($q) => $q->whereHas('purchase'))
                    ->sum('quantity'),
                'total_sold' => (int) SaleItem::where('product_id', $product->id)
                    ->when($allowed !== null, fn ($q) => $q->whereHas('sale'))
                    ->sum('quantity'),
                'last_purchase' => PurchaseItem::query()
                    ->with(['purchase:id,ref_id,purchase_date'])
                    ->where('product_id', $product->id)
                    ->when($allowed !== null, fn ($q) => $q->whereHas('purchase'))
                    ->latest('id')
                    ->first()?->purchase,
            ],
            'purchases' => $purchases,
            'movements' => $movements,
        ]);
    }

    /**
     * Display the product creation form.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'categories' => ProductCategory::with('children')->orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'stores' => StoreAccess::activeQuery($request->user())->get(['id', 'name', 'code']),
            'currentStoreId' => StoreAccess::selectedStoreId($request->user()),
            'product' => null,
        ]);
    }

    public function edit(Request $request, Product $product): Response
    {
        $this->ensureProductVisible($request->user(), $product);

        $product->load(['category', 'subcategory', 'supplier', 'images', 'brand']);

        return Inertia::render('Admin/Products/Form', [
            'categories' => ProductCategory::with('children')->orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'stores' => StoreAccess::activeQuery($request->user())->get(['id', 'name', 'code']),
            'currentStoreId' => StoreAccess::selectedStoreId($request->user()),
            'product' => $product,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:product_categories,id'],
            'subcategory_id' => ['nullable', 'exists:product_categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'description' => ['nullable', 'string'],
            'specifications' => ['nullable', 'string'],
            'specs' => ['nullable', 'array', 'max:40'],
            'specs.*.label' => ['nullable', 'string', 'max:100'],
            'specs.*.value' => ['nullable', 'string', 'max:255'],
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
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
        ]);

        $product = DB::transaction(function () use ($data, $request) {
            $specs = $this->normalizeSpecs($data['specs'] ?? [], $data['specifications'] ?? null);

            $product = Product::create([
                'ref_id' => ReferenceGenerator::generate('product'),
                'sku' => $this->generateSku($data['category_id'] ?? null),
                'name' => $data['name'],
                'category_id' => $data['category_id'] ?? null,
                'subcategory_id' => $data['subcategory_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'description' => $data['description'] ?? null,
                'specifications' => $specs['summary'],
                'specifications_json' => $specs['rows'],
                'unit' => $data['unit'] ?? 'pcs',
                'barcode' => $data['barcode'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'cost_price' => $data['cost_price'],
                'selling_price' => $data['selling_price'],
                'reorder_level' => $data['reorder_level'] ?? 0,
                'status' => $data['status'] ?? 'active',
                'is_featured' => $request->boolean('is_featured'),
                'is_visible_online' => $request->boolean('is_visible_online', true),
                'allow_online_purchase' => $request->boolean('allow_online_purchase', true),
            ]);

            $storeId = $this->authorizeStore($request, isset($data['store_id']) ? (int) $data['store_id'] : null);

            if (($data['opening_quantity'] ?? 0) > 0 && (float) $data['cost_price'] >= 0) {
                $this->inventory->setOpeningStock(
                    $product,
                    (int) $data['opening_quantity'],
                    (float) $data['cost_price'],
                    $request->user()->id,
                    $storeId,
                );
            } else {
                $this->inventory->ensureStorePivot($product, $storeId);
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

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->ensureProductVisible($request->user(), $product);

        /** @var array $data */
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:product_categories,id'],
            'subcategory_id' => ['nullable', 'exists:product_categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'description' => ['nullable', 'string'],
            'specifications' => ['nullable', 'string'],
            'specs' => ['nullable', 'array', 'max:40'],
            'specs.*.label' => ['nullable', 'string', 'max:100'],
            'specs.*.value' => ['nullable', 'string', 'max:255'],
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
            'store_id' => ['nullable', 'exists:stores,id'],
        ]);

        $old = $product->toArray();
        $specs = $this->normalizeSpecs($data['specs'] ?? [], $data['specifications'] ?? null);
        $product->update([
            'name' => $data['name'],
            'category_id' => $data['category_id'] ?? null,
            'subcategory_id' => $data['subcategory_id'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'description' => $data['description'] ?? null,
            'specifications' => $specs['summary'],
            'specifications_json' => $specs['rows'],
            'unit' => $data['unit'] ?? 'pcs',
            'barcode' => $data['barcode'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'cost_price' => $data['cost_price'],
            'selling_price' => $data['selling_price'],
            'reorder_level' => $data['reorder_level'] ?? 0,
            'status' => $data['status'] ?? 'active',
            'is_featured' => $request->boolean('is_featured'),
            'is_visible_online' => $request->boolean('is_visible_online', true),
            'allow_online_purchase' => $request->boolean('allow_online_purchase', true),
        ]);

        // Handle store change - ensure product is available in the new store
        if (! empty($data['store_id'])) {
            $storeId = (int) $data['store_id'];
            $this->inventory->ensureStorePivot($product, $storeId);
        }

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

    public function destroy(Product $product, Request $request): RedirectResponse
    {
        $this->ensureProductVisible($request->user(), $product);

        $sku = $product->sku;
        $name = $product->name;
        $product->delete();
        AuditLogger::log('deleted', 'product', $product->id, "Deleted product {$sku} - {$name}");

        return back()->with('success', 'Product deleted.');
    }

    /**
     * Validation rules for a bulk product id list.
     *
     * Restricted users may only ever act on products that exist in one of
     * their own branches, so a tampered id list fails validation outright
     * instead of silently reaching another branch's catalogue.
     *
     * @return array<int, mixed>
     */
    protected function visibleProductRule(Request $request): array
    {
        $visible = $this->visibleProductIds($request->user());

        if ($visible === null) {
            return ['integer', 'exists:products,id'];
        }

        return ['integer', Rule::in($visible === [] ? [-1] : $visible)];
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => $this->visibleProductRule($request)])['ids'];

        $products = Product::whereIn('id', $ids)->get();
        $count = $products->count();
        $names = $products->pluck('sku', 'name')->map(fn ($n, $s) => "{$s} ({$n})")->join(', ');

        Product::whereIn('id', $ids)->delete();

        AuditLogger::log('bulk_deleted', 'product', null, "Bulk deleted {$count} products: {$names}");

        return back()->with('success', "Deleted {$count} product(s).");
    }

    public function bulkStatus(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => $this->visibleProductRule($request),
            'action' => ['required', 'in:activate,deactivate'],
        ]);

        $status = $data['action'] === 'activate' ? 'active' : 'inactive';
        $count = Product::whereIn('id', $data['ids'])->update(['status' => $status]);

        AuditLogger::log('bulk_status', 'product', null, "Bulk updated {$count} products to {$status}");

        return back()->with('success', "Updated {$count} product(s) to {$status}.");
    }

    public function bulkFeatured(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => $this->visibleProductRule($request),
            'action' => ['required', 'in:featured_on,featured_off'],
        ]);

        $featured = $data['action'] === 'featured_on';
        $count = Product::whereIn('id', $data['ids'])->update(['is_featured' => $featured]);

        AuditLogger::log('bulk_featured', 'product', null, "Bulk updated {$count} products is_featured={$featured}");

        return back()->with('success', "Updated {$count} product(s).");
    }

    public function bulkOnline(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => $this->visibleProductRule($request),
            'action' => ['required', 'in:online_on,online_off'],
        ]);

        $online = $data['action'] === 'online_on';
        $count = Product::whereIn('id', $data['ids'])->update(['is_visible_online' => $online]);

        AuditLogger::log('bulk_online', 'product', null, "Bulk updated {$count} products is_visible_online={$online}");

        return back()->with('success', "Updated {$count} product(s).");
    }

    public function bulkBrand(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => $this->visibleProductRule($request),
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
        ]);

        $count = Product::whereIn('id', $data['ids'])->update(['brand_id' => $data['brand_id']]);

        $brandName = $data['brand_id'] ? Brand::find($data['brand_id'])->name : 'None';
        AuditLogger::log('bulk_brand', 'product', null, "Bulk updated {$count} products brand to {$brandName}");

        return back()->with('success', "Updated brand for {$count} product(s) to {$brandName}.");
    }

    public function bulkAdjustStock(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => $this->visibleProductRule($request),
            'quantity_change' => ['required', 'integer', 'not_in:0'],
            'type' => ['required', 'in:adjustment,damage,return'],
            'reason' => ['required', 'string', 'max:255'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
        ]);

        $storeId = $this->authorizeStore($request, isset($data['store_id']) ? (int) $data['store_id'] : null);
        $userId = $request->user()->id;
        $change = (int) $data['quantity_change'];

        $count = 0;
        foreach ($data['ids'] as $productId) {
            $product = Product::findOrFail($productId);
            $reference = 'BULK-'.$data['type'].'-'.now()->format('YmdHis');

            if ($change > 0) {
                $this->inventory->inbound(
                    product: $product,
                    quantity: $change,
                    type: $data['type'] === 'return' ? StockMovement::TYPE_RETURN : StockMovement::TYPE_ADJUSTMENT,
                    reference: $reference,
                    reason: $data['reason'],
                    documentType: 'bulk_adjustment',
                    documentId: null,
                    userId: $userId,
                    storeId: $storeId,
                );
            } else {
                $this->inventory->outbound(
                    product: $product,
                    quantity: abs($change),
                    type: $data['type'] === 'damage' ? StockMovement::TYPE_DAMAGE : StockMovement::TYPE_ADJUSTMENT,
                    reference: $reference,
                    reason: $data['reason'],
                    documentType: 'bulk_adjustment',
                    documentId: null,
                    userId: $userId,
                    storeId: $storeId,
                );
            }

            $count++;
        }

        AuditLogger::log('bulk_adjust_stock', 'product', null, "Bulk {$data['type']} of {$data['quantity_change']} for {$count} products: {$data['reason']}");

        return back()->with('success', "Adjusted stock for {$count} product(s).");
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => $this->visibleProductRule($request),
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

        $storeId = $this->authorizeStore($request, (int) $data['store_id']);

        $count = 0;
        foreach ($data['ids'] as $productId) {
            $product = Product::findOrFail($productId);
            // Attach product to store if not already attached
            if (! $product->stores()->where('stores.id', $storeId)->exists()) {
                $product->stores()->attach($storeId, [
                    'current_quantity' => 0,
                    'reorder_level' => 10,
                    'selling_price' => $product->selling_price,
                    'average_cost' => $product->cost_price,
                ]);
            }
            $count++;
        }

        $store = Store::findOrFail($storeId);
        AuditLogger::log('bulk_store', 'product', null, "Bulk added {$count} products to store {$store->name}");

        return back()->with('success', "Added {$count} product(s) to {$store->name}.");
    }

    public function stockMovements(Request $request): Response
    {
        $storeId = StoreAccess::selectedStoreId($request->user());

        $base = StockMovement::query()
            ->when($request->product_id, fn ($q, $p) => $q->where('product_id', $p))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->from, fn ($q, $d) => $q->whereDate('movement_date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('movement_date', '<=', $d));

        $movements = (clone $base)
            ->with(['product', 'user', 'fromStore', 'toStore'])
            ->latest('movement_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $rows = (clone $base)
            ->selectRaw('type, COUNT(*) as entries, SUM(quantity_change) as units')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $summary = [
            'entries' => (int) (clone $base)->count(),
            'units_in' => (int) (clone $base)->where('quantity_change', '>', 0)->sum('quantity_change'),
            'units_out' => abs((int) (clone $base)->where('quantity_change', '<', 0)->sum('quantity_change')),
        ];
        $summary['net'] = $summary['units_in'] - $summary['units_out'];

        return Inertia::render('Admin/Inventory/Movements', [
            'movements' => $movements,
            'products' => $this->productsForStorePicker($request),
            'filters' => $request->only(['product_id', 'type', 'from', 'to']),
            'types' => $this->movementTypeDefinitions($rows),
            'summary' => $summary,
            'activeFilterCount' => collect($request->only(['product_id', 'type', 'from', 'to']))
                ->filter(fn ($v) => $v !== null && $v !== '')
                ->count(),
        ]);
    }

    /**
     * Movement types with plain-language explanations and live totals.
     *
     * @param  Collection<string, object>  $rows
     * @return array<int, array{value: string, label: string, direction: string, description: string, entries: int, units: int}>
     */
    protected function movementTypeDefinitions($rows): array
    {
        $definitions = [
            StockMovement::TYPE_OPENING => ['Opening balance', 'in', 'Stock you counted and loaded when the system was set up.'],
            StockMovement::TYPE_PURCHASE => ['Purchase', 'in', 'Stock that arrived from a supplier through a Purchase.'],
            StockMovement::TYPE_SALE => ['Sale', 'out', 'Stock that left the shelf when you made a Sale.'],
            StockMovement::TYPE_PROJECT_ISSUE => ['Project issue', 'out', 'Stock sent out and used on an installation project.'],
            StockMovement::TYPE_PROJECT_RETURN => ['Project return', 'in', 'Unused stock brought back from a project.'],
            StockMovement::TYPE_DAMAGE => ['Damage / write-off', 'out', 'Stock removed because it was broken, spoiled or expired.'],
            StockMovement::TYPE_RETURN => ['Return to stock', 'in', 'Stock put back after a customer or supplier returned it.'],
            StockMovement::TYPE_ADJUSTMENT => ['Correction', 'both', 'A manual correction to make the count match reality.'],
        ];

        $out = [];

        foreach ($definitions as $value => [$label, $direction, $description]) {
            $out[] = [
                'value' => $value,
                'label' => $label,
                'direction' => $direction,
                'description' => $description,
                'entries' => (int) ($rows[$value]->entries ?? 0),
                'units' => (int) ($rows[$value]->units ?? 0),
            ];
        }

        return $out;
    }

    /**
     * Active products the actor may actually adjust, limited to their branches.
     */
    protected function productsForStorePicker(Request $request)
    {
        $allowed = $request->user()?->allowedStoreIds();
        $storeId = StoreAccess::selectedStoreId($request->user());

        return Product::where('status', 'active')
            ->when($storeId, fn ($q) => $q->whereHas('stores', fn ($q) => $q->where('stores.id', $storeId)))
            ->when(! $storeId && $allowed !== null, fn ($q) => $q->whereHas('stores', fn ($q) => $q->whereIn('stores.id', $allowed)))
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);
    }

    public function adjustStock(Request $request, Product $product): RedirectResponse
    {
        $this->ensureProductVisible($request->user(), $product);

        $data = $request->validate([
            'quantity_change' => ['required', 'integer', 'not_in:0'],
            'type' => ['required', 'in:damage,adjustment,return'],
            'reason' => ['required', 'string', 'max:500'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
        ]);

        $change = (int) $data['quantity_change'];
        $storeId = $this->authorizeStore($request, isset($data['store_id']) ? (int) $data['store_id'] : null);

        if ($change > 0) {
            $this->inventory->inbound(
                $product,
                $change,
                $data['type'] === 'return' ? StockMovement::TYPE_RETURN : StockMovement::TYPE_ADJUSTMENT,
                $product->sku.'|ADJ',
                reason: $data['reason'],
                documentType: 'adjustment',
                documentId: $product->id,
                userId: $request->user()->id,
                storeId: $storeId,
            );
        } else {
            $this->inventory->outbound(
                $product,
                abs($change),
                $data['type'] === 'damage' ? StockMovement::TYPE_DAMAGE : StockMovement::TYPE_ADJUSTMENT,
                $product->sku.'|ADJ',
                reason: $data['reason'],
                documentType: 'adjustment',
                documentId: $product->id,
                userId: $request->user()->id,
                storeId: $storeId,
            );
        }

        AuditLogger::log('stock_adjustment', 'product', $product->id,
            "Stock adjusted by {$change} ({$data['type']}): {$data['reason']}");

        return back()->with('success', 'Stock adjusted.');
    }

    /**
     * Display the stock adjustment page.
     *
     * An adjustment always targets exactly one branch, so the page resolves
     * that branch up front and shows per-branch quantities for it.
     */
    public function adjustStockPage(Request $request): Response
    {
        $user = $request->user();
        $stores = StoreAccess::activeQuery($user)->get(['id', 'name', 'code']);

        $activeStoreId = $request->integer('store_id') ?: StoreAccess::selectedStoreId($user);

        if (! $activeStoreId || ! StoreAccess::canAccess($user, $activeStoreId)) {
            $activeStoreId = $stores->first()?->id;
        }

        $products = Product::where('status', 'active')
            ->when($activeStoreId, fn ($q) => $q->whereHas('stores', fn ($q) => $q->where('stores.id', $activeStoreId)))
            ->with(['images', 'stores' => fn ($q) => $q->when($activeStoreId, fn ($sq) => $sq->where('stores.id', $activeStoreId))])
            ->orderBy('name')
            ->get()
            ->map(function ($p) use ($activeStoreId) {
                $pivot = $activeStoreId ? $p->stores->firstWhere('id', $activeStoreId)?->pivot : null;

                return [
                    'id' => $p->id,
                    'sku' => $p->sku,
                    'name' => $p->name,
                    'unit' => $p->unit,
                    'selling_price' => $pivot && $pivot->selling_price !== null ? (float) $pivot->selling_price : (float) $p->selling_price,
                    'current_quantity' => $pivot ? (int) $pivot->current_quantity : 0,
                    'reorder_level' => $pivot ? (int) $pivot->reorder_level : (int) $p->reorder_level,
                    'image' => $p->images->first()?->path ?? '/images/landing/solar_panels_sky.jpg',
                ];
            })
            ->values();

        // The branch selector on this page is a ?store_id query param, so it can
        // differ from the session selection the global scope clamps to. The scope
        // is therefore swapped for an explicit filter on the branch shown here.
        $movementScope = $activeStoreId
            ? [$activeStoreId]
            : ($user?->allowedStoreIds() ?? []);

        $movements = StockMovement::withoutGlobalScope('store')
            ->with(['product:id,name,sku', 'user:id,name'])
            ->whereIn('type', [StockMovement::TYPE_ADJUSTMENT, StockMovement::TYPE_DAMAGE, StockMovement::TYPE_RETURN])
            ->when($movementScope, fn ($q) => $q->whereIn('store_id', $movementScope))
            ->latest('movement_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Inventory/Adjust', [
            'products' => $products,
            'movements' => $movements,
            'stores' => $stores,
            'activeStoreId' => $activeStoreId,
        ]);
    }

    /**
     * Handle stock adjustment submission.
     */
    public function adjustStockAction(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'in:adjustment,damage,return'],
            'direction' => ['nullable', 'in:increase,decrease'],
            'reason' => ['required', 'string', 'max:500'],
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ], [
            'store_id.required' => 'Choose the branch you are adjusting.',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $storeId = $this->authorizeStore($request, (int) $data['store_id']);

        // A hidden product must not be confirmed to exist, but a product the
        // user can see in another branch deserves a readable message.
        $this->ensureProductVisible($request->user(), $product);

        if (! DB::table('product_store')
            ->where('product_id', $product->id)
            ->where('store_id', $storeId)
            ->exists()) {
            throw ValidationException::withMessages([
                'product_id' => 'That product is not stocked at the branch you selected.',
            ]);
        }

        $quantity = (int) $data['quantity'];
        $type = $data['type'];

        // Damage always removes stock and a return always adds it. Only a
        // plain correction lets the user pick the direction.
        $decrease = $type === 'damage'
            || ($type === 'adjustment' && ($data['direction'] ?? 'increase') === 'decrease');

        if ($decrease) {
            $this->inventory->outbound(
                product: $product,
                quantity: $quantity,
                type: $type === 'damage' ? StockMovement::TYPE_DAMAGE : StockMovement::TYPE_ADJUSTMENT,
                reference: $product->sku.($type === 'damage' ? '|DMG' : '|ADJ'),
                reason: $data['reason'],
                documentType: 'adjustment',
                documentId: $product->id,
                userId: $request->user()->id,
                storeId: $storeId,
            );
        } else {
            $this->inventory->inbound(
                product: $product,
                quantity: $quantity,
                type: $type === 'return' ? StockMovement::TYPE_RETURN : StockMovement::TYPE_ADJUSTMENT,
                reference: $product->sku.($type === 'return' ? '|RET' : '|ADJ'),
                reason: $data['reason'],
                documentType: 'adjustment',
                documentId: $product->id,
                userId: $request->user()->id,
                storeId: $storeId,
            );
        }

        $signed = ($decrease ? -1 : 1) * $quantity;
        $resulting = $this->inventory->getStoreStock($product, $storeId);
        $previous = $resulting - $signed;
        $branch = Store::find($storeId)?->name;

        AuditLogger::log('stock_adjustment', 'product', $product->id,
            "Stock adjusted by {$signed} ({$type}) at store #{$storeId}: {$data['reason']}");

        return back()->with('success', "{$product->name} at {$branch} is now {$resulting} (was {$previous}).");
    }

    /**
     * Normalize structured product specifications.
     *
     * Converts the label/value rows (from the form's spec builder) into a
     * clean list of rows for the `specifications_json` column + a plain-text
     * "Label: value" summary for the legacy `specifications` column so older
     * consumers keep working. Rows missing a label or value are dropped.
     *
     * @param  array<int, array{label?: string, value?: string}>  $specs
     */
    protected function normalizeSpecs(array $specs = [], ?string $legacy = null): array
    {
        $rows = [];

        foreach ($specs as $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = trim((string) ($row['label'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));
            if ($label === '' || $value === '') {
                continue;
            }
            $rows[] = ['label' => $label, 'value' => $value];
        }

        if ($rows === [] && $legacy !== null && trim($legacy) !== '') {
            foreach (preg_split('/\R/', trim($legacy)) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                [$label, $value] = array_pad(explode(':', $line, 2), 2, '');
                $label = trim((string) $label);
                $value = trim((string) $value);
                if ($label !== '' && $value !== '') {
                    $rows[] = ['label' => $label, 'value' => $value];
                }
            }
        }

        $summary = $rows !== []
            ? implode("\n", array_map(fn ($row) => $row['label'].': '.$row['value'], $rows))
            : null;

        return [
            'rows' => $rows !== [] ? $rows : null,
            'summary' => $summary,
        ];
    }

    /**
     * Generate a unique, human-readable SKU based on the product category.
     *
     * Format: EV-{CATEGORY}-{SEQUENCE} e.g. EV-SOL-0031, EV-PRD-0032
     */
    protected function generateSku(?int $categoryId): string
    {
        $code = 'PRD';

        if ($categoryId) {
            $name = ProductCategory::whereKey($categoryId)->value('name');
            if ($name) {
                $first = preg_split('/[\s\-]+/', trim($name))[0] ?? '';
                $clean = preg_replace('/[^A-Za-z0-9]/', '', $first);
                if ($clean !== '') {
                    $code = strtoupper(substr($clean, 0, 3));
                }
            }
        }

        $seq = ((int) Product::withTrashed()->max('id')) + 1;

        return sprintf('EV-%s-%04d', $code, $seq);
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
