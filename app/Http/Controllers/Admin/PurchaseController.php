<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\AuditLogger;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function __construct(protected PurchaseService $purchaseService) {}

    public function index(Request $request): Response
    {
        $purchases = Purchase::query()
            ->with(['supplier'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('ref_id', 'like', "%{$s}%")
                    ->orWhere('invoice_no', 'like', "%{$s}%")
                    ->orWhereHas('supplier', fn ($c) => $c->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->from, fn ($q, $d) => $q->whereDate('purchase_date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('purchase_date', '<=', $d))
            ->latest('purchase_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Purchases/Index', [
            'purchases' => $purchases,
            'filters' => $request->only(['search', 'status', 'from', 'to']),
        ]);
    }

    public function create(Request $request): Response
    {
        $storeId = session('admin_store_id');
        $isAllStores = ! $storeId;

        $products = Product::where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($p) use ($storeId) {
                $available = $p->current_quantity;
                $cost_price = $p->cost_price;
                if ($storeId) {
                    $pivot = $p->stores->firstWhere('id', $storeId)?->pivot;
                    $available = $pivot ? $pivot->current_quantity : 0;
                    $cost_price = $pivot ? $pivot->average_cost : $p->cost_price;
                }

                return [
                    'id' => $p->id,
                    'sku' => $p->sku,
                    'name' => $p->name,
                    'unit' => $p->unit,
                    'cost_price' => $cost_price,
                    'current_quantity' => $available,
                ];
            })
            ->values();

        $requestedIds = collect(explode(',', (string) $request->query('products', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique();

        $preselectedIds = $requestedIds->isEmpty()
            ? collect()
            : Product::where('status', 'active')
                ->whereIn('id', $requestedIds)
                ->orderBy('name')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values();

        return Inertia::render('Admin/Purchases/Form', [
            'products' => $products,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name', 'phone']),
            'preselected_product_ids' => $preselectedIds,
            'can_view_cost' => auth()->user()->hasPermission('inventory.view_cost'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'purchase_date' => ['required', 'date'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'supplier_name' => ['nullable', 'string', 'max:255', 'required_without:supplier_id'],
            'supplier_phone' => ['nullable', 'string', 'max:100'],
            'invoice_no' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string'],
        ]);

        try {
            $purchase = $this->purchaseService->createPurchase($data, $data['items'], $request->user()->id);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        AuditLogger::log('created', 'purchase', $purchase->id, "Created purchase {$purchase->ref_id} for ₦{$purchase->total}");

        return redirect()->route('admin.purchases.show', $purchase)->with('success', 'Purchase recorded.');
    }

    public function show(Purchase $purchase): Response
    {
        $purchase->load(['supplier', 'items.product']);

        $payments = Payment::where('document_type', 'purchase')
            ->where('document_id', $purchase->id)
            ->latest('payment_date')
            ->get();

        return Inertia::render('Admin/Purchases/Show', [
            'purchase' => $purchase,
            'payments' => $payments,
            'can_view_cost' => auth()->user()->hasPermission('inventory.view_cost'),
        ]);
    }

    public function destroy(Purchase $purchase, Request $request): RedirectResponse
    {
        $request->validate(['void_reason' => ['required', 'string', 'max:500']]);

        try {
            $this->purchaseService->voidPurchase($purchase, $request->void_reason, $request->user()->id);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        AuditLogger::log('voided', 'purchase', $purchase->id, "Voided purchase {$purchase->ref_id}: {$request->void_reason}");

        return back()->with('success', 'Purchase voided.');
    }
}
