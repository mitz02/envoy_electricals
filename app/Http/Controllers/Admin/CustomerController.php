<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\AuditLogger;
use App\Services\ReferenceGenerator;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request): \Inertia\Response
    {
        $customers = Customer::query()
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('ref_id', 'like', "%{$s}%");
            }))
            ->withCount(['sales as total_sales_sum' => fn ($q) => $q->where('status', 'completed')])
            ->withSum('sales as lifetime_purchases', 'total')
            ->withSum(['sales as outstanding' => fn ($q) => $q->where('status', 'completed')], 'balance')
            ->when($request->boolean('owing'), fn ($q) => $q->whereRaw('(SELECT COALESCE(SUM(balance), 0) FROM sales WHERE sales.customer_id = customers.id AND sales.status = ? AND sales.deleted_at IS NULL) > 0', ['completed']))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total_outstanding' => (float) Sale::where('status', 'completed')->sum('balance'),
            'owing_customers' => Sale::where('status', 'completed')
                ->where('balance', '>', 0)
                ->distinct()
                ->count('customer_id'),
        ];

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
            'summary' => $summary,
            'filters' => $request->only(['search', 'owing']),
        ]);
    }

    public function create(): \Inertia\Response
    {
        return Inertia::render('Admin/Customers/Form', ['customer' => null]);
    }

    public function edit(Customer $customer): \Inertia\Response
    {
        return Inertia::render('Admin/Customers/Form', ['customer' => $customer]);
    }

    public function quickCreate(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'customer_type' => ['nullable', 'in:walk_in,regular,corporate'],
        ]);

        $customer = Customer::create([...$data, 'ref_id' => ReferenceGenerator::generate('customer')]);

        AuditLogger::log('created', 'customer', $customer->id, "Created customer {$customer->name}");

        return response()->json([
            'customer' => $customer->only([
                'id', 'name', 'phone', 'email', 'address', 'location', 'customer_type',
            ]),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'customer_type' => ['nullable', 'in:walk_in,regular,corporate'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer = Customer::create([...$data, 'ref_id' => ReferenceGenerator::generate('customer')]);

        AuditLogger::log('created', 'customer', $customer->id, "Created customer {$customer->name}");

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Customer created.');
    }

    public function update(Request $request, Customer $customer): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'customer_type' => ['nullable', 'in:walk_in,regular,corporate'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer->update($data);

        AuditLogger::log('updated', 'customer', $customer->id, "Updated customer {$customer->name}");

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Customer updated.');
    }

    public function show(Customer $customer): \Inertia\Response
    {
        $customer->load(['sales' => fn ($q) => $q->latest('sale_date')->take(10)]);

        $totals = (object) [
            'sales_sum' => (float) $customer->sales()->where('status', 'completed')->sum('total'),
            'outstanding' => (float) $customer->sales()->where('status', 'completed')->sum('balance'),
            'count' => $customer->sales()->where('status', 'completed')->count(),
        ];

        $recentPayments = \App\Models\Payment::where('customer_id', $customer->id)
            ->latest('payment_date')->take(10)->get();

        return Inertia::render('Admin/Customers/Show', [
            'customer' => $customer,
            'totals' => $totals,
            'recent_payments' => $recentPayments,
        ]);
    }

    public function destroy(Customer $customer): \Illuminate\Http\RedirectResponse
    {
        $name = $customer->name;
        $customer->delete();
        AuditLogger::log('deleted', 'customer', $customer->id, "Deleted customer {$name}");

        return redirect()->route('admin.customers.index')->with('success', 'Customer deleted.');
    }
}