<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\AuditLogger;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SaleController extends Controller
{
    public function __construct(protected SaleService $saleService) {}

    public function index(Request $request): \Inertia\Response
    {
        $sales = Sale::query()
            ->with([
                'salesperson',
                'customer' => fn ($q) => $q->withSum(
                    ['sales as outstanding' => fn ($s) => $s->where('status', 'completed')],
                    'balance',
                ),
            ])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('invoice_no', 'like', "%{$s}%")
                    ->orWhere('ref_id', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->from, fn ($q, $d) => $q->whereDate('sale_date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('sale_date', '<=', $d))
            ->latest('sale_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Sales/Index', [
            'sales' => $sales,
            'filters' => $request->only(['search', 'status', 'from', 'to']),
            'total_revenue' => $sales->total(),
        ]);
    }

    public function create(): \Inertia\Response
    {
        return Inertia::render('Admin/Sales/Form', [
            'products' => Product::where('status', 'active')
                ->with('images')
                ->orderBy('name')
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'sku' => $p->sku,
                    'name' => $p->name,
                    'unit' => $p->unit,
                    'selling_price' => $p->selling_price,
                    'current_quantity' => $p->current_quantity,
                    'image' => $p->images->first()?->path ?? '/images/landing/solar_panels_sky.jpg',
                ])
                ->values(),
            'customers' => Customer::withSum(
                ['sales as outstanding' => fn ($q) => $q->where('status', 'completed')],
                'balance',
            )
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'customer_type'])
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'phone' => $c->phone,
                    'customer_type' => $c->customer_type,
                    'outstanding' => (float) $c->outstanding,
                    'has_outstanding' => (float) $c->outstanding > 0,
                ]),
            'tax_rate' => (float) (\App\Models\Setting::where('key', 'tax.rate')->value('value') ?? 0),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'sale_date' => ['required', 'date'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255', 'required_without:customer_id'],
            'customer_phone' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string'],
        ]);

        try {
            $sale = $this->saleService->createSale($data, $data['items'], $request->user()->id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        AuditLogger::log('created', 'sale', $sale->id, "Created sale {$sale->invoice_no} for ₦{$sale->total}");

        $message = 'Sale completed.';

        if ($sale->customer_id) {
            $sale->load('customer');

            $prior = (float) Sale::where('customer_id', $sale->customer_id)
                ->where('status', 'completed')
                ->where('id', '!=', $sale->id)
                ->sum('balance');

            if ($prior > 0) {
                $message .= " Note: {$sale->customer->name} owes ₦" . number_format($prior, 2, '.', ',') . ' from previous invoices.';
            }
        }

        return redirect()->route('admin.sales.show', $sale)->with('success', $message);
    }

    public function show(Sale $sale): \Inertia\Response
    {
        $sale->load([
            'salesperson',
            'items.product',
            'customer' => fn ($q) => $q->withSum(
                ['sales as outstanding' => fn ($s) => $s->where('status', 'completed')],
                'balance',
            ),
        ]);

        $payments = \App\Models\Payment::where('document_type', 'sale')
            ->where('document_id', $sale->id)
            ->latest('payment_date')
            ->get();

        return Inertia::render('Admin/Sales/Show', [
            'sale' => $sale,
            'payments' => $payments,
            'can_view_cost' => request()->user()->hasPermission('inventory.view_cost') || request()->user()->hasPermission('reports.profit'),
        ]);
    }

    public function destroy(Sale $sale, Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['void_reason' => ['required', 'string', 'max:500']]);

        try {
            $this->saleService->voidSale($sale, $request->void_reason, $request->user()->id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        AuditLogger::log('voided', 'sale', $sale->id, "Voided sale {$sale->invoice_no}: {$request->void_reason}");

        return back()->with('success', 'Sale voided and stock restored.');
    }
}