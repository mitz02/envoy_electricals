<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\Staff;
use App\Services\StaffService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayrollController extends Controller
{
    public function index(Request $request): \Inertia\Response
    {
        $year = $request->get('period_year', now()->format('Y'));
        $month = str_pad((string) $request->get('period_month', now()->format('n')), 2, '0', STR_PAD_LEFT);

        $payrolls = Payroll::query()
            ->with(['staff'])
            ->when($request->search, fn ($q, $s) => $q->whereHas('staff', fn (Builder $staff) =>
                $staff->where('name', 'like', "%{$s}%")->orWhere('ref_id', 'like', "%{$s}%")
            ))
            ->when($request->staff_id, fn ($q, $id) => $q->where('staff_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($year && $month, fn ($q) => $q->forPeriod($year, $month))
            ->latest('period_year')
            ->orderByDesc('period_month')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $period = Payroll::query()->when($year && $month, fn ($q) => $q->forPeriod($year, $month));

        $summary = [
            'count' => (clone $period)->count(),
            'gross' => round((float) (clone $period)->sum('base_salary') + (float) (clone $period)->sum('allowance'), 2),
            'bonus' => round((float) (clone $period)->sum('bonus'), 2),
            'advance' => round((float) (clone $period)->sum('advance'), 2),
            'deduction' => round((float) (clone $period)->sum('deduction'), 2),
            'net_payable' => round((float) (clone $period)->sum('amount_paid'), 2),
            'paid' => round((float) (clone $period)->paid()->sum('amount_paid'), 2),
        ];

        return Inertia::render('Admin/Payroll/Index', [
            'payrolls' => $payrolls,
            'summary' => $summary,
            'staff' => Staff::orderBy('name')->get(['id', 'name']),
            'statuses' => [Payroll::STATUS_PENDING, Payroll::STATUS_PAID, Payroll::STATUS_CANCELLED],
            'filters' => $request->only(['search', 'staff_id', 'status', 'period_year', 'period_month']),
        ]);
    }

    public function create(): \Inertia\Response
    {
        return Inertia::render('Admin/Payroll/Form', [
            'payroll' => null,
            'staff' => Staff::active()->orderBy('name')->get(['id', 'ref_id', 'name', 'position', 'base_salary', 'housing_allowance', 'transport_allowance', 'other_allowance']),
            'month' => now()->format('Y-m'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);

        try {
            $payroll = app(StaffService::class)->createPayroll($data, $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('admin.payroll.show', $payroll->id)->with('success', "Payroll prepared — net pay ₦" . number_format($payroll->amount_paid, 2) . '.');
    }

    public function show(Payroll $payroll): \Inertia\Response
    {
        $payroll->load(['staff', 'creator', 'payments']);

        return Inertia::render('Admin/Payroll/Show', [
            'payroll' => $payroll,
            'statuses' => [Payroll::STATUS_PENDING, Payroll::STATUS_PAID, Payroll::STATUS_CANCELLED],
        ]);
    }

    public function edit(Payroll $payroll): \Inertia\Response
    {
        abort_if($payroll->status !== Payroll::STATUS_PENDING, 403, 'Only pending payroll records can be edited.');

        return Inertia::render('Admin/Payroll/Form', [
            'payroll' => $payroll,
            'staff' => Staff::active()->orderBy('name')->get(['id', 'ref_id', 'name', 'position', 'base_salary', 'housing_allowance', 'transport_allowance', 'other_allowance']),
            'month' => null,
        ]);
    }

    public function update(Request $request, Payroll $payroll)
    {
        abort_if($payroll->status !== Payroll::STATUS_PENDING, 403, 'Only pending payroll records can be edited.');

        $data = $this->validated($request, $payroll);

        try {
            app(StaffService::class)->updatePayroll($payroll, $data, $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('admin.payroll.show', $payroll->id)->with('success', 'Payroll updated.');
    }

    public function pay(Request $request, Payroll $payroll)
    {
        $data = $request->validate([
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            app(StaffService::class)->markPaid($payroll, $data, $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['pay' => $e->getMessage()]);
        }

        return redirect()->route('admin.payroll.show', $payroll->id)->with('success', "Payroll marked as paid (₦" . number_format($payroll->amount_paid, 2) . ').');
    }

    public function destroy(Request $request, Payroll $payroll)
    {
        try {
            app(StaffService::class)->void($payroll, $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['pay' => $e->getMessage()]);
        }

        return redirect()->route('admin.payroll.index')->with('success', 'Payroll cancelled and any payment reversed.');
    }

    protected function validated(Request $request, ?Payroll $payroll): array
    {
        return $request->validate([
            'staff_id' => ['required', 'exists:staff,id'],
            'period_month' => ['required', 'integer', 'between:1,12'],
            'period_year' => ['required', 'integer', 'between:2020,2100'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'allowance' => ['nullable', 'numeric', 'min:0'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'advance' => ['nullable', 'numeric', 'min:0'],
            'deduction' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}