<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\Project;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Staff;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $today = Carbon::today();
        $storeId = session('admin_store_id');
        $isAllStores = ! $storeId;

        // Helper to apply store scope
        $scope = fn ($query) => $storeId ? $query->where('store_id', $storeId) : $query;

        // ---- Selected reporting period (drives the KPI range) ----
        $periods = [
            'today' => ['label' => 'Today',        'start' => fn (Carbon $t) => $t->copy()],
            'week' => ['label' => 'Last 7 days',  'start' => fn (Carbon $t) => $t->copy()->subDays(6)],
            'month' => ['label' => 'This Month',   'start' => fn (Carbon $t) => $t->copy()->startOfMonth()],
            '30days' => ['label' => 'Last 30 days', 'start' => fn (Carbon $t) => $t->copy()->subDays(29)],
            'year' => ['label' => 'This Year',    'start' => fn (Carbon $t) => $t->copy()->startOfYear()],
        ];

        $periodKey = (string) $request->query('period');
        if (! array_key_exists($periodKey, $periods)) {
            $periodKey = 'month';
        }

        $rangeStart = $periods[$periodKey]['start']($today);
        $rangeLabel = $periods[$periodKey]['label'];

        $elapsedDays = (int) $rangeStart->diffInDays($today) + 1;
        $prevRangeStart = $rangeStart->copy()->subDays($elapsedDays);
        $prevRangeEnd = $rangeStart->copy()->subDay();

        // ---- Sales & revenue ----
        $salesBetween = fn ($from, $to) => (float) $scope(Sale::where('status', 'completed'))
            ->whereBetween('sale_date', [$from, $to])
            ->sum('total');

        $rangeSales = $salesBetween($rangeStart, $today);
        $prevRangeSales = $salesBetween($prevRangeStart, $prevRangeEnd);

        $todaySales = $salesBetween($today, $today);
        $todayOrdersCount = $scope(Sale::where('status', 'completed'))->whereDate('sale_date', $today)->count();
        $rangeOrdersCount = $scope(Sale::where('status', 'completed'))->whereBetween('sale_date', [$rangeStart, $today])->count();

        $rangeCollected = (float) $scope(Sale::where('status', 'completed'))
            ->whereBetween('sale_date', [$rangeStart, $today])
            ->sum('amount_paid');
        $prevRangeCollected = (float) $scope(Sale::where('status', 'completed'))
            ->whereBetween('sale_date', [$prevRangeStart, $prevRangeEnd])
            ->sum('amount_paid');

        $growth = fn (float $current, float $previous) => $previous > 0
            ? round((($current - $previous) / $previous) * 100, 1)
            : ($current > 0 ? 100 : 0);

        $salesGrowth = $growth($rangeSales, $prevRangeSales);
        $collectedGrowth = $growth($rangeCollected, $prevRangeCollected);

        // ---- Cost of goods, expenses & profit ----
        $rangeCost = (float) SaleItem::query()
            ->whereHas('sale', fn ($q) => $scope($q->where('status', 'completed'))
                ->whereBetween('sale_date', [$rangeStart, $today]))
            ->sum(DB::raw('unit_cost * quantity'));

        $rangeGrossProfit = round($rangeSales - $rangeCost, 2);
        $rangeExpenses = (float) $scope(Expense::where('status', 'recorded'))
            ->whereBetween('expense_date', [$rangeStart, $today])
            ->sum('amount');
        $rangeNetProfit = round($rangeGrossProfit - $rangeExpenses, 2);
        $profitMargin = $rangeSales > 0 ? round(($rangeGrossProfit / $rangeSales) * 100, 1) : 0;

        // ---- Receivables & payables ----
        $receivables = round((float) $scope(Sale::where('status', 'completed'))->where('balance', '>', 0)->sum('balance'), 2);
        $payables = round((float) $scope(Purchase::where('status', 'completed'))->where('balance', '>', 0)->sum('balance'), 2);

        // ---- Inventory & stock health ----
        if ($isAllStores) {
            $inventoryValue = round((float) Product::sum(DB::raw('current_quantity * average_cost')), 2);
            $totalProducts = Product::count();
            $lowStock = Product::where('current_quantity', '>', 0)
                ->whereRaw('current_quantity <= reorder_level')
                ->count();
            $outOfStock = Product::where('current_quantity', '<=', 0)->count();
        } else {
            $inventoryValue = round((float) DB::table('product_store')
                ->where('store_id', $storeId)
                ->sum(DB::raw('current_quantity * average_cost')), 2);
            $totalProducts = DB::table('product_store')
                ->where('store_id', $storeId)
                ->distinct('product_id')
                ->count('product_id');
            $lowStock = DB::table('product_store')
                ->where('store_id', $storeId)
                ->where('current_quantity', '>', 0)
                ->whereRaw('current_quantity <= reorder_level')
                ->count();
            $outOfStock = DB::table('product_store')
                ->where('store_id', $storeId)
                ->where('current_quantity', '<=', 0)
                ->count();
        }

        // ---- Solar & electrical projects ----
        $activeProjects = $scope(Project::whereNotIn('status', ['completed', 'cancelled', 'draft']))->count();
        $completedProjects = $scope(Project::where('status', 'completed'))->count();
        $projectProfit = round((float) $scope(Project::whereNotIn('status', ['cancelled']))->sum('gross_profit'), 2);
        $totalProjectValue = round((float) $scope(Project::whereNotIn('status', ['cancelled']))->sum('contract_value'), 2);

        // ---- Staff, payroll & assets ----
        $activeStaff = $scope(Staff::where('is_active', true))->count();
        $rangePayroll = round((float) Payroll::paid()->whereHas('staff', fn ($q) => $scope($q))->where('payment_date', '>=', $rangeStart)->sum('amount_paid'), 2);
        $assetsValue = round((float) $scope(Asset::where('status', '!=', Asset::STATUS_DISPOSED))->sum('current_value'), 2);

        // ---- Customer segments (walk_in / regular / corporate) ----
        $totalCustomers = $scope(Customer::query())->count();
        $segmentCounts = $scope(Customer::query())
            ->selectRaw('customer_type, COUNT(*) as c')
            ->groupBy('customer_type')
            ->pluck('c', 'customer_type');

        $customerBreakdown = collect([
            ['key' => 'walk_in',   'label' => 'Walk-in Customers', 'color' => 'blue'],
            ['key' => 'regular',   'label' => 'Regular Customers', 'color' => 'emerald'],
            ['key' => 'corporate', 'label' => 'Corporate Clients', 'color' => 'amber'],
        ])->map(function (array $segment) use ($segmentCounts, $totalCustomers) {
            $count = (int) ($segmentCounts[$segment['key']] ?? 0);

            return [
                'label' => $segment['label'],
                'count' => $count,
                'color' => $segment['color'],
                'share' => $totalCustomers > 0 ? round(($count / $totalCustomers) * 100) : 0,
            ];
        })->values()->all();

        // ---- Weekday sales volume (last 60 days) ----
        $weekdaySales = ['Sun' => 0, 'Mon' => 0, 'Tue' => 0, 'Wed' => 0, 'Thu' => 0, 'Fri' => 0, 'Sat' => 0];

        $scope(Sale::where('status', 'completed'))
            ->whereBetween('sale_date', [$today->copy()->subDays(60), $today])
            ->orderBy('sale_date')
            ->get(['sale_date', 'total'])
            ->each(function ($sale) use (&$weekdaySales) {
                $weekdaySales[$sale->sale_date->format('D')] += (float) $sale->total;
            });

        $maxWeekdayValue = max($weekdaySales);
        $peakDay = null;
        foreach ($weekdaySales as $day => $val) {
            if ($val > 0 && $val === $maxWeekdayValue) {
                $peakDay = $day;
                break;
            }
        }

        $weekdayData = collect($weekdaySales)->map(function ($val, $day) use ($peakDay, $maxWeekdayValue) {
            return [
                'day' => $day,
                'value' => round($val),
                'is_peak' => $day === $peakDay && $val > 0,
                'height_pct' => $maxWeekdayValue > 0 ? round(($val / $maxWeekdayValue) * 100) : 0,
            ];
        })->values()->all();

        // ---- 30-day sales trend (current vs same window last month) ----
        $trendStart = $today->copy()->subDays(29);
        $prevTrendStart = $trendStart->copy()->subDays(30);
        $prevTrendEnd = $trendStart->copy()->subDay();

        $currentByDate = $scope(Sale::where('status', 'completed'))
            ->whereBetween('sale_date', [$trendStart, $today])
            ->get(['sale_date', 'total'])
            ->groupBy(fn ($s) => $s->sale_date->toDateString())
            ->map(fn ($group) => (float) $group->sum('total'));

        $prevByDate = $scope(Sale::where('status', 'completed'))
            ->whereBetween('sale_date', [$prevTrendStart, $prevTrendEnd])
            ->get(['sale_date', 'total'])
            ->groupBy(fn ($s) => $s->sale_date->toDateString())
            ->map(fn ($group) => (float) $group->sum('total'));

        $trendCurve = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $prevDate = $date->copy()->subMonth();

            $trendCurve[] = [
                'day' => $date->format('j'),
                'date_label' => $date->format('M j, Y'),
                'short_label' => $date->format('j M'),
                'current' => (float) ($currentByDate[$date->toDateString()] ?? 0),
                'previous' => (float) ($prevByDate[$prevDate->toDateString()] ?? 0),
            ];
        }

        // ---- Repeat customer rate ----
        $repeatCustomers = $scope(Customer::has('sales', '>=', 2))->count();
        $repeatCustomerRate = $totalCustomers > 0 ? round(($repeatCustomers / $totalCustomers) * 100) : 0;

        // ---- Recent feeds (real records only) ----
        $recentProjects = $scope(Project::with('customer'))
            ->latest('start_date')
            ->take(5)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'ref_id' => $p->ref_id,
                'name' => $p->name,
                'status' => $p->status,
                'contract_value' => $p->contract_value,
                'gross_profit' => $p->gross_profit,
                'balance' => $p->balance,
                'customer' => $p->customer?->name ?? 'Walk-in Client',
                'start_date' => $p->start_date?->format('d M, Y') ?? 'N/A',
            ]);

        $recentSales = $scope(Sale::with(['customer', 'salesperson']))
            ->where('status', 'completed')
            ->latest('sale_date')
            ->take(6)
            ->get()
            ->map(fn ($sale) => [
                'id' => $sale->id,
                'ref_id' => $sale->ref_id,
                'invoice_no' => $sale->invoice_no,
                'date' => $sale->sale_date->format('d M, Y'),
                'customer' => $sale->customer?->name ?? 'Walk-in Customer',
                'total' => $sale->total,
                'balance' => $sale->balance,
                'payment_method' => $sale->payment_method ?? 'Transfer',
            ]);

        $recentPurchases = $scope(Purchase::with('supplier'))
            ->where('status', 'completed')
            ->latest('purchase_date')
            ->take(5)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'ref_id' => $p->ref_id,
                'date' => $p->purchase_date->format('d M, Y'),
                'supplier' => $p->supplier?->name ?? 'Direct Importer',
                'total' => $p->total,
                'balance' => $p->balance,
            ]);

        $recentExpenses = $scope(Expense::with('category'))
            ->where('status', 'recorded')
            ->latest('expense_date')
            ->take(5)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'ref_id' => $e->ref_id,
                'date' => $e->expense_date->format('d M, Y'),
                'description' => $e->description,
                'category' => $e->category?->name ?? 'Operations',
                'amount' => $e->amount,
            ]);

        // ---- Top fast-moving products (this period) ----
        $topProducts = SaleItem::query()
            ->whereHas('sale', fn ($q) => $scope($q->where('status', 'completed'))->whereBetween('sale_date', [$rangeStart, $today]))
            ->with(['product.category'])
            ->select('product_id')
            ->selectRaw('SUM(quantity) as qty, SUM(total) as revenue')
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->take(5)
            ->get()
            ->map(function ($row) use ($storeId) {
                $product = $row->product;
                $stock = 0;
                $reorder = 0;
                $sellingPrice = 0;

                if ($storeId && $product) {
                    $pivot = $product->stores()->where('store_id', $storeId)->first();
                    if ($pivot) {
                        $stock = $pivot->pivot->current_quantity ?? 0;
                        $reorder = $pivot->pivot->reorder_level ?? 0;
                        $sellingPrice = $pivot->pivot->selling_price ?? $product->selling_price;
                    }
                } else {
                    $stock = $product?->current_quantity ?? 0;
                    $reorder = $product?->reorder_level ?? 0;
                    $sellingPrice = $product?->selling_price ?? 0;
                }

                return [
                    'id' => $product?->ref_id ?? 'EV-PROD-'.str_pad((string) $row->product_id, 6, '0', STR_PAD_LEFT),
                    'name' => $product?->name ?? 'Product #'.$row->product_id,
                    'category' => $product?->category?->name ?? 'General',
                    'qty' => (int) $row->qty,
                    'revenue' => round((float) $row->revenue, 2),
                    'price' => $sellingPrice,
                    'stock' => $stock,
                    'reorder' => $reorder,
                    'sku' => $product?->sku ?? '',
                ];
            })
            ->values();

        $allowedProfit = $request->user()->hasPermission('reports.profit');

        return Inertia::render('Admin/Dashboard', [
            'period' => $periodKey,
            'stats' => [
                'period' => $periodKey,
                'period_label' => $rangeLabel,
                'today_sales' => $todaySales,
                'today_orders_count' => $todayOrdersCount,
                'month_sales' => $rangeSales,
                'range_orders_count' => $rangeOrdersCount,
                'sales_growth' => $salesGrowth,
                'month_collected' => $rangeCollected,
                'collected_growth' => $collectedGrowth,
                'month_expenses' => $rangeExpenses,
                'month_gross_profit' => $allowedProfit ? $rangeGrossProfit : null,
                'month_net_profit' => $allowedProfit ? $rangeNetProfit : null,
                'profit_margin' => $profitMargin,
                'receivables' => $allowedProfit ? $receivables : null,
                'payables' => $allowedProfit ? $payables : null,
                'inventory_value' => $allowedProfit ? $inventoryValue : null,
                'total_products' => $totalProducts,
                'low_stock' => $lowStock,
                'out_of_stock' => $outOfStock,
                'active_projects' => $activeProjects,
                'completed_projects' => $completedProjects,
                'project_profit' => $allowedProfit ? $projectProfit : null,
                'total_project_value' => $totalProjectValue,
                'active_staff' => $activeStaff,
                'month_payroll' => $allowedProfit ? $rangePayroll : null,
                'assets_value' => $allowedProfit ? $assetsValue : null,
            ],
            'trend_curve' => $trendCurve,
            'weekday_data' => $weekdayData,
            'customer_breakdown' => $customerBreakdown,
            'repeat_customer_rate' => $repeatCustomerRate,
            'top_products' => $topProducts,
            'recent_sales' => $recentSales,
            'recent_purchases' => $recentPurchases,
            'recent_expenses' => $recentExpenses,
            'recent_projects' => $recentProjects,
        ]);
    }
}
