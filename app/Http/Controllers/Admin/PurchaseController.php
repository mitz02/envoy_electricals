<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\AuditLogger;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PurchaseController extends Controller
{
    public function __construct(protected PurchaseService $purchaseService) {}

    public function index(Request $request): \Inertia\Response
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

    public function create(): \Inertia\Response
    {
        return Inertia::render('Admin/Purchases/Form', [
            'products' => Product::where('status', 'active')->orderBy('name')
                ->get(['id', 'sku', 'name', 'cost_price', 'current_quantity', 'unit']),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name', 'phone']),
            'can_view_cost' => auth()->user()->hasPermission('inventory.view_cost'),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'purchase_date' => ['required', 'date'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'supplier_name' => ['nullable', 'string', 'max:255', 'required_without:supplier_id'],
            'supplier_phone' => ['nullable', 'string', 'max:100'],
            'invoice_no' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string'],
        ]);

        try {
            $purchase = $this->purchaseService->createPurchase($data, $data['items'], $request->user()->id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        AuditLogger::log('created', 'purchase', $purchase->id, "Created purchase {$purchase->ref_id} for ₦{$purchase->total}");

        return redirect()->route('admin.purchases.show', $purchase)->with('success', 'Purchase recorded.');
    }

    public function show(Purchase $purchase): \Inertia\Response
    {
        $purchase->load(['supplier', 'items.product']);

        $payments = \App\Models\Payment::where('document_type', 'purchase')
            ->where('document_id', $purchase->id)
            ->latest('payment_date')
            ->get();

        return Inertia::render('Admin/Purchases/Show', [
            'purchase' => $purchase,
            'payments' => $payments,
            'can_view_cost' => auth()->user()->hasPermission('inventory.view_cost'),
        ]);
    }

    public function destroy(Purchase $purchase, Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['void_reason' => ['required', 'string', 'max:500']]);

        try {
            $this->purchaseService->voidPurchase($purchase, $request->void_reason, $request->user()->id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        AuditLogger::log('voided', 'purchase', $purchase->id, "Voided purchase {$purchase->ref_id}: {$request->void_reason}");

        return back()->with('success', 'Purchase voided.');
    }
}