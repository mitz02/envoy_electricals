<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Services\AuditLogger;
use App\Services\ReferenceGenerator;
use App\Support\StoreAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $customers = Customer::query()
            ->with('store')
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

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Customers/Form', [
            'customer' => null,
            'stores' => $this->formStores($request),
            'currentStoreId' => StoreAccess::selectedStoreId($request->user())
                ?? $request->user()?->store_id
                ?? null,
        ]);
    }

    public function edit(Request $request, Customer $customer): Response
    {
        return Inertia::render('Admin/Customers/Form', [
            'customer' => $customer->load('store'),
            'stores' => $this->formStores($request),
            'currentStoreId' => $customer->store_id,
        ]);
    }

    public function quickCreate(Request $request): JsonResponse
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

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $customer = new Customer([...$data, 'ref_id' => ReferenceGenerator::generate('customer')]);

        if ($data['store_id'] === null) {
            $customer->skipStoreAutoAssign = true;
        }

        $customer->save();

        AuditLogger::log('created', 'customer', $customer->id, "Created customer {$customer->name}");

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Customer created.');
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $data = $this->validated($request);

        $customer->update($data);

        AuditLogger::log('updated', 'customer', $customer->id, "Updated customer {$customer->name}");

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Customer updated.');
    }

    public function show(Customer $customer): Response
    {
        $customer->load([
            'store',
            'sales' => fn ($q) => $q->latest('sale_date')->take(10),
        ]);

        $totals = (object) [
            'sales_sum' => (float) $customer->sales()->where('status', 'completed')->sum('total'),
            'outstanding' => (float) $customer->sales()->where('status', 'completed')->sum('balance'),
            'count' => $customer->sales()->where('status', 'completed')->count(),
        ];

        $recentPayments = Payment::where('customer_id', $customer->id)
            ->latest('payment_date')->take(10)->get();

        return Inertia::render('Admin/Customers/Show', [
            'customer' => $customer,
            'totals' => $totals,
            'recent_payments' => $recentPayments,
        ]);
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $name = $customer->name;
        $customer->delete();
        AuditLogger::log('deleted', 'customer', $customer->id, "Deleted customer {$name}");

        return redirect()->route('admin.customers.index')->with('success', 'Customer deleted.');
    }

    /**
     * Shared create/update validation. An empty store selection stays
     * unassigned (visible under "All Stores") until an admin assigns a branch.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'customer_type' => ['required', 'in:walk_in,regular,corporate'],
            'store_id' => ['nullable', Rule::exists('stores', 'id')],
            'notes' => ['nullable', 'string'],
        ]);

        $data['store_id'] = $this->authorizeStore($request, ! empty($data['store_id'] ?? null) ? (int) $data['store_id'] : null);

        return $data;
    }

    protected function authorizeStore(Request $request, ?int $storeId): ?int
    {
        if ($storeId === null) {
            return null;
        }

        if (! StoreAccess::canAccess($request->user(), $storeId)) {
            throw ValidationException::withMessages([
                'store_id' => 'You do not have access to that branch.',
            ]);
        }

        return $storeId;
    }

    /**
     * The branches the current admin may assign (active ones only).
     */
    protected function formStores(Request $request)
    {
        return StoreAccess::activeQuery($request->user())->get(['id', 'name', 'code']);
    }
}
