<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OrderService;
use App\Services\ReferenceGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class BuyerController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    /**
     * List every registered website buyer with their order history.
     */
    public function index(Request $request): Response
    {
        $buyerRoleId = Role::where('slug', 'buyer')->value('id');

        $buyers = User::where('role_id', $buyerRoleId)
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            }))
            ->withCount(['orders as orders_count'])
            ->withSum('orders as orders_total', 'total')
            ->withMax('orders as last_order_at', 'created_at')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Buyers/Index', [
            'buyers' => $buyers,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show a single buyer's account, order history and payments.
     */
    public function show(Request $request, User $buyer): Response
    {
        $this->ensureIsBuyer($buyer);

        $buyer->load(['customer']);

        $allOrders = $buyer->orders()->get();

        $outstanding = 0.0;
        foreach ($allOrders as $order) {
            $outstanding += $this->orders->balance($order);
        }

        $stats = [
            'orders_count' => $allOrders->count(),
            'pending_count' => $allOrders->whereIn('status', ['pending', 'processing'])->count(),
            'total_spent' => round((float) $allOrders->sum('total'), 2),
            'outstanding' => round($outstanding, 2),
        ];

        $orders = $buyer->orders()
            ->latest('id')
            ->with('items')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Order $order) => $this->orderSummary($order));

        $payments = Payment::where('document_type', 'order')
            ->whereIn('document_id', $allOrders->pluck('id'))
            ->latest('id')
            ->limit(10)
            ->get(['id', 'ref_id', 'amount', 'payment_method', 'status', 'payment_date', 'created_at']);

        return Inertia::render('Admin/Buyers/Show', [
            'buyer' => $buyer->only(['id', 'name', 'email', 'phone', 'created_at']),
            'customer' => $buyer->customer?->only(['id', 'ref_id', 'name', 'email', 'phone']),
            'stats' => $stats,
            'orders' => $orders,
            'payments' => $payments,
        ]);
    }

    /**
     * Show the buyer edit form.
     */
    public function edit(User $buyer): Response
    {
        $this->ensureIsBuyer($buyer);

        return Inertia::render('Admin/Buyers/Form', [
            'buyer' => $buyer->load('customer')->only([
                'id', 'name', 'email', 'phone', 'is_active', 'created_at', 'customer',
            ]),
        ]);
    }

    /**
     * Update the buyer's account. The linked customer record is kept in sync.
     */
    public function update(Request $request, User $buyer): RedirectResponse
    {
        $this->ensureIsBuyer($buyer);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($buyer->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['sometimes', 'boolean'],
            'password' => ['sometimes', 'nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $update = collect($data)->except(['password'])->all();
        $update['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : $buyer->is_active;

        $buyer->update($update);

        if (! empty($data['password'])) {
            $buyer->update(['password' => $data['password']]);
        }

        $this->syncCustomer($buyer, $data);

        AuditLogger::log('updated', 'buyer', $buyer->id, "Updated buyer {$buyer->name} ({$buyer->email})");

        return redirect()->route('admin.buyers.show', $buyer)->with('success', 'Buyer account updated.');
    }

    /**
     * Delete a buyer account. Their order history and any linked customer
     * record are kept for admin bookkeeping, but the login account is gone.
     */
    public function destroy(User $buyer): RedirectResponse
    {
        $this->ensureIsBuyer($buyer);

        $name = $buyer->name;
        $buyer->delete();

        AuditLogger::log('deleted', 'buyer', $buyer->id, "Deleted buyer {$name}");

        return redirect()->route('admin.buyers.index')->with('success', "Buyer account for {$name} deleted.");
    }

    /**
     * Impersonate a buyer: the admin is signed in as the buyer so they can
     * see exactly what the customer sees. A "Return to admin" strip lets the
     * staff member hop straight back to their own account.
     */
    public function impersonate(Request $request, User $buyer): RedirectResponse
    {
        $this->ensureIsBuyer($buyer);

        abort_if($buyer->id === $request->user()->id, 422, 'You are already signed in as this buyer.');

        $impersonatorId = $request->user()->id;

        Auth::login($buyer);

        session(['impersonator_id' => $impersonatorId, 'impersonated_id' => $buyer->id]);

        return redirect()->route('buyer.dashboard')
            ->with('success', 'You are now viewing the account as '.$buyer->name.'.');
    }

    /**
     * Drop impersonation and sign the staff member back in.
     */
    public function leaveImpersonation(Request $request): RedirectResponse
    {
        $impersonatorId = (int) session('impersonator_id');

        if ($impersonatorId >= 1) {
            Auth::loginUsingId($impersonatorId);

            return redirect()->route('admin.buyers.index')
                ->with('success', 'You are back in your admin account.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    protected function ensureIsBuyer(User $buyer): void
    {
        abort_unless($buyer->role?->slug === 'buyer', 404);
    }

    protected function syncCustomer(User $buyer, array $data): void
    {
        $customer = $buyer->customer;

        if ($customer) {
            $customer->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? $customer->phone,
            ]);

            return;
        }

        $customer = $buyer->customer()->make([
            'ref_id' => ReferenceGenerator::generate('customer'),
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'customer_type' => 'regular',
        ]);
        // Keep buyer-linked customers unassigned until an admin assigns a store.
        $customer->skipStoreAutoAssign = true;
        $customer->save();
    }

    protected function orderSummary(Order $order): array
    {
        return [
            'id' => $order->id,
            'ref_id' => $order->ref_id,
            'created_at' => $order->created_at?->toISOString(),
            'status' => $order->status,
            'fulfillment' => $order->fulfillment,
            'total' => (float) $order->total,
            'paid' => $this->orders->totalPaid($order),
            'balance' => $this->orders->balance($order),
            'item_count' => $order->items->sum('quantity'),
        ];
    }
}
