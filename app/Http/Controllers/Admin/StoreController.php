<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use App\Services\InventoryService;
use App\Support\StoreAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $stores = Store::withCount(['sales', 'purchases', 'customers', 'staff', 'projects', 'expenses'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Stores/Index', [
            'stores' => $stores,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Stores/Form', [
            'store' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:stores,code',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        if ($validated['is_default'] ?? false) {
            Store::where('is_default', true)->update(['is_default' => false]);
        }

        $store = Store::create($validated);

        // Seed product_store with 0 quantity for all existing products so branch is ready immediately
        $products = Product::all(['id', 'reorder_level', 'average_cost', 'cost_price', 'selling_price']);
        foreach ($products as $p) {
            DB::table('product_store')->insert([
                'product_id' => $p->id,
                'store_id' => $store->id,
                'current_quantity' => 0,
                'reorder_level' => $p->reorder_level ?? 0,
                'average_cost' => $p->average_cost ?? $p->cost_price ?? 0,
                'selling_price' => $p->selling_price,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('admin.stores.index')
            ->with('success', 'Store created successfully with initialized product inventory.');
    }

    public function show(Store $store)
    {
        $store->loadCount(['sales', 'purchases', 'customers', 'staff', 'projects', 'expenses', 'users']);

        $inventoryStats = [
            'total_products' => $store->products()->count(),
            'total_stock' => (int) $store->products()->sum('product_store.current_quantity'),
            'total_value' => round((float) $store->products()->sum(DB::raw('product_store.current_quantity * product_store.selling_price')), 2),
            'low_stock' => $store->products()->where('product_store.current_quantity', '>', 0)
                ->whereRaw('product_store.current_quantity <= product_store.reorder_level')->count(),
        ];

        $recentSales = $store->sales()->with('customer')->latest('sale_date')->take(5)->get();

        $recentTransfers = StockMovement::withoutGlobalScopes()
            ->where(fn ($q) => $q->where('store_id', $store->id)->orWhere('from_store_id', $store->id)->orWhere('to_store_id', $store->id))
            ->whereIn('type', ['transfer_out', 'transfer_in'])
            ->with(['product', 'fromStore', 'toStore', 'user'])
            ->latest()
            ->take(10)
            ->get();

        $allStores = Store::active()->where('id', '!=', $store->id)->get(['id', 'name', 'code']);
        $products = Product::where('status', 'active')->orderBy('name')->get(['id', 'name', 'sku']);

        return Inertia::render('Admin/Stores/Show', [
            'store' => $store,
            'inventoryStats' => $inventoryStats,
            'recentSales' => $recentSales,
            'recentTransfers' => $recentTransfers,
            'allStores' => $allStores,
            'products' => $products,
        ]);
    }

    public function edit(Store $store)
    {
        return Inertia::render('Admin/Stores/Form', [
            'store' => $store,
        ]);
    }

    public function update(Request $request, Store $store)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:stores,code,'.$store->id,
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        if ($validated['is_default'] ?? false) {
            Store::where('is_default', true)->where('id', '!=', $store->id)->update(['is_default' => false]);
        }

        $store->update($validated);

        return redirect()->route('admin.stores.index')
            ->with('success', 'Store updated successfully.');
    }

    public function destroy(Store $store)
    {
        if ($store->is_default) {
            return back()->with('error', 'Cannot delete the default store.');
        }

        $hasData = $store->sales()->exists()
            || $store->purchases()->exists()
            || $store->customers()->exists()
            || $store->staff()->exists()
            || $store->projects()->exists()
            || $store->expenses()->exists();

        if ($hasData) {
            return back()->with('error', 'Cannot delete store with existing data. Deactivate it instead.');
        }

        DB::table('product_store')->where('store_id', $store->id)->delete();
        $store->delete();

        return redirect()->route('admin.stores.index')
            ->with('success', 'Store deleted successfully.');
    }

    public function transferStock(Request $request, InventoryService $inventory)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'from_store_id' => ['required', 'exists:stores,id'],
            'to_store_id' => ['required', 'exists:stores,id', 'different:from_store_id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        // Both ends of a transfer must be branches the actor can actually use.
        foreach (['from_store_id', 'to_store_id'] as $field) {
            if (! StoreAccess::canAccess($request->user(), (int) $data[$field])) {
                throw ValidationException::withMessages([
                    $field => 'You do not have access to that branch.',
                ]);
            }
        }

        $product = Product::findOrFail($data['product_id']);

        try {
            $result = $inventory->transferStock(
                product: $product,
                quantity: (int) $data['quantity'],
                fromStoreId: (int) $data['from_store_id'],
                toStoreId: (int) $data['to_store_id'],
                userId: $request->user()->id,
                reason: $data['reason'] ?? null,
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', "Transferred {$data['quantity']} unit(s) of {$product->name} successfully. Reference: {$result['reference']}");
    }
}
