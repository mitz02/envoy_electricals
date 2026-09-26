<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    public function index(Request $request): Response
    {
        $user = $request->user()->load(['customer']);

        $allOrders = Order::where('user_id', $user->id)->get();

        $stats = [
            'orders_count' => $allOrders->count(),
            'pending_count' => $allOrders->whereIn('status', ['pending', 'processing'])->count(),
            'total_spent' => round((float) $allOrders->sum('total'), 2),
        ];

        $outstanding = 0.0;
        foreach ($allOrders as $order) {
            $outstanding += $this->orders->balance($order);
        }

        $stats['outstanding'] = round($outstanding, 2);

        $recentOrders = Order::where('user_id', $user->id)
            ->latest('id')
            ->with('items')
            ->limit(5)
            ->get()
            ->map(fn (Order $order) => $this->orderSummary($order));

        $orderIds = $allOrders->pluck('id');

        $payments = Payment::where('document_type', 'order')
            ->whereIn('document_id', $orderIds)
            ->latest('id')
            ->limit(10)
            ->get(['id', 'ref_id', 'amount', 'payment_method', 'status', 'payment_date', 'created_at']);

        return Inertia::render('Buyer/Dashboard', [
            'user' => $user->only(['id', 'name', 'email', 'phone']),
            'stats' => $stats,
            'recent_orders' => $recentOrders,
            'payments' => $payments,
        ]);
    }

    public function orders(Request $request): Response
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->latest('id')
            ->with('items')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Order $order) => $this->orderSummary($order));

        return Inertia::render('Buyer/Orders', [
            'orders' => $orders,
        ]);
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
