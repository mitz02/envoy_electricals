<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\Staff;
use App\Models\User;
use App\Services\StaffService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function index(Request $request): \Inertia\Response
    {
        $staff = Staff::query()
            ->withCount(['payrolls'])
            ->withSum('paidPayrolls as total_paid', 'amount_paid')
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('position', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('ref_id', 'like', "%{$s}%");
            }))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->latest('date_joined')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($s) => $s);

        $monthStart = now()->startOfMonth()->toDateString();

        $summary = [
            'total' => Staff::count(),
            'active' => Staff::where('is_active', true)->count(),
            'month_paid' => round((float) Payroll::paid()->where('payment_date', '>=', $monthStart)->sum('amount_paid'), 2),
        ];

        return Inertia::render('Admin/Staff/Index', [
            'staff' => $staff,
            'summary' => $summary,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): \Inertia\Response
    {
        return Inertia::render('Admin/Staff/Form', [
            'staff' => null,
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $staff = app(StaffService::class)->create($data, $request->user()->id);

        return redirect()->route('admin.staff.show', $staff->id)->with('success', 'Staff profile created.');
    }

    public function show(Staff $staff): \Inertia\Response
    {
        $staff->load(['user']);

        return Inertia::render('Admin/Staff/Show', [
            'staff' => $staff,
            'payrolls' => $staff->payrolls()->with('creator')->latest('period_year')
                ->orderByDesc('period_month')->paginate(20)->withQueryString(),
        ]);
    }

    public function edit(Staff $staff): \Inertia\Response
    {
        return Inertia::render('Admin/Staff/Form', [
            'staff' => $staff,
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function update(Request $request, Staff $staff)
    {
        $data = $this->validated($request, $staff->id);

        app(StaffService::class)->update($staff, $data, $request->user()->id);

        return redirect()->route('admin.staff.show', $staff->id)->with('success', 'Staff profile updated.');
    }

    public function destroy(Request $request, Staff $staff)
    {
        app(StaffService::class)->destroy($staff, $request->user()->id);

        return redirect()->route('admin.staff.index')->with('success', 'Staff record removed.');
    }

    protected function validated(Request $request, int $ignoreId = 0): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', 'unique:staff,email,' . $ignoreId],
            'date_joined' => ['nullable', 'date'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'housing_allowance' => ['nullable', 'numeric', 'min:0'],
            'transport_allowance' => ['nullable', 'numeric', 'min:0'],
            'other_allowance' => ['nullable', 'numeric', 'min:0'],
            'user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}