<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\Project;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    protected array $filters = ['from', 'to'];

    public function sales(Request $request): Response
    {
        $fromRaw = $request->input('from');
        $toRaw = $request->input('to');
        $from = ($fromRaw && $fromRaw !== 'null' && $fromRaw !== 'undefined') ? $request->date('from') : null;
        $to = ($toRaw && $toRaw !== 'null' && $toRaw !== 'undefined') ? $request->date('to') : null;
        $groupBy = $request->get('group', 'day');

        $query = Sale::where('status', 'completed')
            ->when($from, fn ($q, $d) => $q->whereDate('sale_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->whereDate('sale_date', '<=', $d));

        $rows = match ($groupBy) {
            'week' => $query->selectRaw("strftime('%Y-W%W', sale_date) as period, SUM(total) as total, COUNT(*) as count")
                ->groupBy('period')->orderBy('period')->get(),
            'month' => $query->selectRaw("strftime('%Y-%m', sale_date) as period, SUM(total) as total, COUNT(*) as count")
                ->groupBy('period')->orderBy('period')->get(),
            default => $query->selectRaw('date(sale_date) as period, SUM(total) as total, COUNT(*) as count')
                ->groupBy('period')->orderBy('period')->get(),
        };

        // Breakdowns
        $byStaff = Sale::where('status', 'completed')->when($from, fn ($q, $d) => $q->whereDate('sale_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->whereDate('sale_date', '<=', $d))
            ->with('salesperson')
            ->selectRaw('salesperson_id, SUM(total) as total, COUNT(*) as count')
            ->groupBy('salesperson_id')->orderByDesc('total')->get();

        $byMethod = Sale::where('status', 'completed')->when($from, fn ($q, $d) => $q->whereDate('sale_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->whereDate('sale_date', '<=', $d))
            ->selectRaw('COALESCE(payment_method, "unknown") as method, SUM(total) as total, COUNT(*) as count')
            ->groupBy('method')->orderByDesc('total')->get();

        $totals = [
            'revenue' => round((float) Sale::where('status', 'completed')->when($from, fn ($q, $d) => $q->whereDate('sale_date', '>=', $d))
                ->when($to, fn ($q, $d) => $q->whereDate('sale_date', '<=', $d))->sum('total'), 2),
            'collected' => round((float) Sale::where('status', 'completed')->when($from, fn ($q, $d) => $q->whereDate('sale_date', '>=', $d))
                ->when($to, fn ($q, $d) => $q->whereDate('sale_date', '<=', $d))->sum('amount_paid'), 2),
            'count' => (int) Sale::where('status', 'completed')->when($from, fn ($q, $d) => $q->whereDate('sale_date', '>=', $d))
                ->when($to, fn ($q, $d) => $q->whereDate('sale_date', '<=', $d))->count(),
        ];

        return Inertia::render('Admin/Reports/Sales', [
            'rows' => $rows,
            'by_staff' => $byStaff,
            'by_method' => $byMethod,
            'totals' => $totals,
            'filters' => ['from' => $from?->toDateString(), 'to' => $to?->toDateString(), 'group' => $groupBy],
            'can_view_profit' => $request->user()->hasPermission('reports.profit'),
        ]);
    }

    public function inventory(Request $request): Response
    {
        $storeId = session('admin_store_id');

        if ($storeId) {
            $products = Product::query()
                ->join('product_store', function ($join) use ($storeId) {
                    $join->on('products.id', '=', 'product_store.product_id')
                        ->where('product_store.store_id', '=', $storeId);
                })
                ->select(
                    'products.id',
                    'products.ref_id',
                    'products.sku',
                    'products.name',
                    'products.category_id',
                    'product_store.current_quantity',
                    'product_store.reorder_level',
                    'product_store.average_cost',
                    'product_store.selling_price'
                )
                ->with(['category'])
                ->when($request->search, fn ($q, $s) => $q->where('products.name', 'like', "%{$s}%")->orWhere('products.sku', 'like', "%{$s}%"))
                ->when($request->status, fn ($q, $s) => match ($s) {
                    'low' => $q->whereRaw('product_store.current_quantity <= product_store.reorder_level')->where('product_store.current_quantity', '>', 0),
                    'out' => $q->where('product_store.current_quantity', '<=', 0),
                    default => $q,
                })
                ->orderByDesc(DB::raw('product_store.current_quantity * product_store.average_cost'))
                ->paginate(20)
                ->withQueryString();

            $summary = [
                'total_value' => round((float) DB::table('product_store')
                    ->where('store_id', $storeId)
                    ->sum(DB::raw('current_quantity * average_cost')), 2),
                'low' => DB::table('product_store')
                    ->where('store_id', $storeId)
                    ->where('current_quantity', '>', 0)
                    ->whereRaw('current_quantity <= reorder_level')
                    ->count(),
                'out' => DB::table('product_store')
                    ->where('store_id', $storeId)
                    ->where('current_quantity', '<=', 0)
                    ->count(),
                'unit_cost' => DB::table('product_store')
                    ->where('store_id', $storeId)
                    ->where('current_quantity', '>', 0)
                    ->count(),
            ];
        } else {
            $products = Product::query()
                ->with(['category'])
                ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"))
                ->when($request->status, fn ($q, $s) => match ($s) {
                    'low' => $q->whereRaw('current_quantity <= reorder_level')->where('current_quantity', '>', 0),
                    'out' => $q->where('current_quantity', '<=', 0),
                    default => $q,
                })
                ->orderByDesc(DB::raw('current_quantity * average_cost'))
                ->paginate(20)
                ->withQueryString();

            $summary = [
                'total_value' => round((float) Product::sum(DB::raw('current_quantity * average_cost')), 2),
                'low' => Product::whereRaw('current_quantity <= reorder_level')->where('current_quantity', '>', 0)->count(),
                'out' => Product::where('current_quantity', '<=', 0)->count(),
                'unit_cost' => Product::where('current_quantity', '>', 0)->count(),
            ];
        }

        return Inertia::render('Admin/Reports/Inventory', [
            'products' => $products,
            'summary' => $summary,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function profit(Request $request): Response
    {
        if (! $request->user()->hasPermission('reports.profit')) {
            abort(403);
        }

        $from = $this->rangeDate($request, 'from');
        $to = $this->rangeDate($request, 'to');

        $salesQuery = Sale::where('status', 'completed')->when($from, fn ($q, $d) => $q->whereDate('sale_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->whereDate('sale_date', '<=', $d));

        $revenue = round((float) (clone $salesQuery)->sum('total'), 2);
        $cogs = round((float) SaleItem::whereHas('sale', fn ($q) => $q->where('status', 'completed')
            ->when($from, fn ($q2, $d) => $q2->whereDate('sale_date', '>=', $d))
            ->when($to, fn ($q2, $d) => $q2->whereDate('sale_date', '<=', $d)))
            ->sum(DB::raw('unit_cost * quantity')), 2);
        $grossProfit = round($revenue - $cogs, 2);
        $grossMargin = $revenue > 0 ? round(($grossProfit / $revenue) * 100, 2) : 0;

        $expenseQuery = Expense::where('status', 'recorded')->when($from, fn ($q, $d) => $q->whereDate('expense_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->whereDate('expense_date', '<=', $d));
        $expenses = round((float) (clone $expenseQuery)->sum('amount'), 2);
        $netProfit = round($grossProfit - $expenses, 2);

        $purchases = round((float) Purchase::where('status', 'completed')->when($from, fn ($q, $d) => $q->whereDate('purchase_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->whereDate('purchase_date', '<=', $d))->sum('total'), 2);

        $expensesByCategory = Expense::where('status', 'recorded')
            ->when($from, fn ($q, $d) => $q->whereDate('expense_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->whereDate('expense_date', '<=', $d))
            ->with('category')
            ->selectRaw('expense_category_id, SUM(amount) as total')
            ->groupBy('expense_category_id')->orderByDesc('total')->get();

        $productProfit = SaleItem::whereHas('sale', fn ($q) => $q->where('status', 'completed')
            ->when($from, fn ($q2, $d) => $q2->whereDate('sale_date', '>=', $d))
            ->when($to, fn ($q2, $d) => $q2->whereDate('sale_date', '<=', $d)))
            ->with('product')
            ->selectRaw('product_id, SUM(quantity) as qty, SUM(total) as revenue, SUM(unit_cost * quantity) as cost, SUM(profit) as profit')
            ->groupBy('product_id')->orderByDesc('profit')->take(15)->get();

        return Inertia::render('Admin/Reports/Profit', [
            'summary' => [
                'revenue' => $revenue, 'cogs' => $cogs, 'gross_profit' => $grossProfit, 'gross_margin' => $grossMargin,
                'expenses' => $expenses, 'net_profit' => $netProfit, 'purchases' => $purchases,
            ],
            'expenses_by_category' => $expensesByCategory,
            'product_profit' => $productProfit,
            'filters' => ['from' => $from?->toDateString(), 'to' => $to?->toDateString()],
        ]);
    }

    public function purchases(Request $request): Response
    {
        $from = $this->rangeDate($request, 'from');
        $to = $this->rangeDate($request, 'to');

        $purchases = Purchase::where('status', 'completed')
            ->when($from, fn ($q, $d) => $q->whereDate('purchase_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->whereDate('purchase_date', '<=', $d))
            ->latest('purchase_date')->paginate(20)->withQueryString();

        $totals = [
            'total' => round((float) Purchase::where('status', 'completed')->when($from, fn ($q, $d) => $q->whereDate('purchase_date', '>=', $d))
                ->when($to, fn ($q, $d) => $q->whereDate('purchase_date', '<=', $d))->sum('total'), 2),
            'paid' => round((float) Purchase::where('status', 'completed')->when($from, fn ($q, $d) => $q->whereDate('purchase_date', '>=', $d))
                ->when($to, fn ($q, $d) => $q->whereDate('purchase_date', '<=', $d))->sum('amount_paid'), 2),
            'payable' => round((float) Purchase::where('status', 'completed')->when($from, fn ($q, $d) => $q->whereDate('purchase_date', '>=', $d))
                ->when($to, fn ($q, $d) => $q->whereDate('purchase_date', '<=', $d))->sum('balance'), 2),
        ];

        return Inertia::render('Admin/Reports/Purchases', [
            'purchases' => $purchases,
            'totals' => $totals,
            'filters' => ['from' => $from?->toDateString(), 'to' => $to?->toDateString()],
        ]);
    }

    public function expenses(Request $request): Response
    {
        $from = $this->rangeDate($request, 'from');
        $to = $this->rangeDate($request, 'to');

        $expenses = Expense::where('status', 'recorded')
            ->when($from, fn ($q, $d) => $q->whereDate('expense_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->whereDate('expense_date', '<=', $d))
            ->with('category')->latest('expense_date')->paginate(20)->withQueryString();

        $total = round((float) Expense::where('status', 'recorded')->when($from, fn ($q, $d) => $q->whereDate('expense_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->whereDate('expense_date', '<=', $d))->sum('amount'), 2);

        return Inertia::render('Admin/Reports/Expenses', [
            'expenses' => $expenses,
            'total' => $total,
            'filters' => ['from' => $from?->toDateString(), 'to' => $to?->toDateString()],
        ]);
    }

    public function projects(Request $request): Response
    {
        $from = $this->rangeDate($request, 'from');
        $to = $this->rangeDate($request, 'to');

        $projects = Project::query()
            ->with('customer')
            ->when($from, fn ($q, $d) => $q->whereDate('start_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->whereDate('start_date', '<=', $d))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('start_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $base = Project::whereNotIn('status', ['cancelled'])
            ->when($from, fn ($q, $d) => $q->whereDate('start_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->whereDate('start_date', '<=', $d));

        $summary = [
            'active' => (clone $base)->whereIn('status', Project::ACTIVE_STATUSES)->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'contract_value' => round((float) (clone $base)->sum('contract_value'), 2),
            'received' => round((float) (clone $base)->sum('amount_received'), 2),
            'project_cost' => round((float) (clone $base)->sum('project_cost'), 2),
            'gross_profit' => round((float) (clone $base)->sum('gross_profit'), 2),
            'outstanding' => round((float) (clone $base)->where('balance', '>', 0)->sum('balance'), 2),
        ];

        $byStatus = Project::query()
            ->when($from, fn ($q, $d) => $q->whereDate('start_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->whereDate('start_date', '<=', $d))
            ->selectRaw('status, COUNT(*) as count, SUM(gross_profit) as profit')
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();

        return Inertia::render('Admin/Reports/Projects', [
            'projects' => $projects,
            'summary' => $summary,
            'by_status' => $byStatus,
            'statuses' => Project::STATUSES,
            'filters' => ['from' => $from?->toDateString(), 'to' => $to?->toDateString(), 'status' => $request->get('status')],
        ]);
    }

    public function payroll(Request $request): Response
    {
        $year = $request->get('period_year', now()->format('Y'));
        $month = str_pad((string) $request->get('period_month', now()->format('n')), 2, '0', STR_PAD_LEFT);

        $query = Payroll::query()->forPeriod($year, $month)
            ->when($request->staff_id, fn ($q, $id) => $q->where('staff_id', $id));

        $summary = [
            'count' => (clone $query)->count(),
            'gross' => round((float) (clone $query)->sum('base_salary') + (float) (clone $query)->sum('allowance'), 2),
            'bonus' => round((float) (clone $query)->sum('bonus'), 2),
            'advance' => round((float) (clone $query)->sum('advance'), 2),
            'deduction' => round((float) (clone $query)->sum('deduction'), 2),
            'net_payable' => round((float) (clone $query)->sum('amount_paid'), 2),
            'paid' => round((float) (clone $query)->paid()->sum('amount_paid'), 2),
        ];

        $byStaff = Payroll::query()->forPeriod($year, $month)
            ->with('staff')
            ->selectRaw('staff_id, COUNT(*) as runs, SUM(base_salary) as base, SUM(allowance) as allowance,
                SUM(bonus) as bonus, SUM(advance) as advance, SUM(deduction) as deduction, SUM(amount_paid) as amount_paid,
                SUM(CASE WHEN status = "paid" THEN amount_paid ELSE 0 END) as paid')
            ->groupBy('staff_id')
            ->orderByDesc('paid')
            ->get();

        $rows = Payroll::query()->forPeriod($year, $month)
            ->with('staff')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Reports/Payroll', [
            'rows' => $rows,
            'by_staff' => $byStaff,
            'summary' => $summary,
            'staff' => Staff::orderBy('name')->get(['id', 'name']),
            'filters' => ['period_year' => $year, 'period_month' => $month, 'staff_id' => $request->get('staff_id')],
        ]);
    }

    public function export(Request $request)
    {
        $type = $request->query('type', 'sales');
        $fromRaw = $request->query('from');
        $toRaw = $request->query('to');
        $from = ($fromRaw && $fromRaw !== 'null' && $fromRaw !== 'undefined') ? $request->date('from') : null;
        $to = ($toRaw && $toRaw !== 'null' && $toRaw !== 'undefined') ? $request->date('to') : null;

        $rows = match ($type) {
            'sales' => Sale::where('status', 'completed')
                ->when($from, fn ($q, $d) => $q->whereDate('sale_date', '>=', $d))
                ->when($to, fn ($q, $d) => $q->whereDate('sale_date', '<=', $d))
                ->with('customer')->get()
                ->map(fn ($s) => [
                    'Invoice' => $s->invoice_no, 'Date' => $s->sale_date->toDateString(),
                    'Customer' => $s->customer?->name ?? 'Walk-in', 'Subtotal' => $s->subtotal,
                    'Discount' => $s->discount, 'Tax' => $s->tax, 'Total' => $s->total,
                    'Paid' => $s->amount_paid, 'Balance' => $s->balance,
                ]),
            'purchases' => Purchase::where('status', 'completed')
                ->when($from, fn ($q, $d) => $q->whereDate('purchase_date', '>=', $d))
                ->when($to, fn ($q, $d) => $q->whereDate('purchase_date', '<=', $d))
                ->with('supplier')->get()
                ->map(fn ($p) => [
                    'Ref' => $p->ref_id, 'Date' => $p->purchase_date->toDateString(),
                    'Supplier' => $p->supplier?->name ?? 'N/A', 'Total' => $p->total,
                    'Paid' => $p->amount_paid, 'Balance' => $p->balance,
                ]),
            'expenses' => Expense::where('status', 'recorded')
                ->when($from, fn ($q, $d) => $q->whereDate('expense_date', '>=', $d))
                ->when($to, fn ($q, $d) => $q->whereDate('expense_date', '<=', $d))
                ->with('category')->get()
                ->map(fn ($e) => [
                    'Ref' => $e->ref_id, 'Date' => $e->expense_date->toDateString(),
                    'Category' => $e->category?->name, 'Description' => $e->description,
                    'Amount' => $e->amount, 'Paid To' => $e->paid_to,
                ]),
            'inventory' => Product::get(['sku', 'name', 'current_quantity', 'average_cost', 'selling_price'])
                ->map(fn ($p) => [
                    'SKU' => $p->sku, 'Name' => $p->name, 'Qty' => $p->current_quantity,
                    'Avg Cost' => $p->average_cost, 'Selling Price' => $p->selling_price,
                ]),
            'projects' => Project::with('customer')
                ->when($from, fn ($q, $d) => $q->whereDate('start_date', '>=', $d))
                ->when($to, fn ($q, $d) => $q->whereDate('start_date', '<=', $d))
                ->get()
                ->map(fn ($p) => [
                    'Ref' => $p->ref_id, 'Name' => $p->name,
                    'Customer' => $p->customer?->name ?? 'N/A', 'Status' => $p->status,
                    'Contract Value' => $p->contract_value, 'Received' => $p->amount_received,
                    'Balance' => $p->balance, 'Project Cost' => $p->project_cost,
                    'Gross Profit' => $p->gross_profit, 'Start Date' => $p->start_date?->toDateString(),
                ]),
            'payroll' => Payroll::with('staff')
                ->when($request->period_year && $request->period_month, fn ($q) => $q->forPeriod(
                    $request->period_year,
                    str_pad((string) $request->period_month, 2, '0', STR_PAD_LEFT)
                ))
                ->get()
                ->map(fn ($p) => [
                    'Ref' => $p->ref_id, 'Staff' => $p->staff?->name ?? 'N/A',
                    'Period' => $p->period, 'Base Salary' => $p->base_salary,
                    'Allowance' => $p->allowance, 'Bonus' => $p->bonus,
                    'Advance' => $p->advance, 'Deduction' => $p->deduction,
                    'Net Pay' => $p->amount_paid, 'Status' => $p->status,
                    'Payment Date' => $p->payment_date?->toDateString(),
                ]),
            default => collect(),
        };

        $filename = strtolower($type).'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, array_values((array) $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function rangeDate(Request $request, string $key): ?Carbon
    {
        $raw = $request->input($key);

        if (! $raw || $raw === 'null' || $raw === 'undefined') {
            return null;
        }

        return $request->date($key);
    }
}
