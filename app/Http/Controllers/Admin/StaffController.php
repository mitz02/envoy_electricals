<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\Role;
use App\Models\Staff;
use App\Services\StaffService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(Request $request): Response
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

    public function create(): Response
    {
        return Inertia::render('Admin/Staff/Form', [
            'staff' => null,
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $staff = app(StaffService::class)->create($data, $request->user()->id);

        return redirect()->route('admin.staff.show', $staff->id)->with('success', 'Staff profile and login account created.');
    }

    public function show(Staff $staff): Response
    {
        $staff->load(['user.stores', 'user.role']);

        return Inertia::render('Admin/Staff/Show', [
            'staff' => $staff,
            'payrolls' => $staff->payrolls()->with('creator')->latest('period_year')
                ->orderByDesc('period_month')->paginate(20)->withQueryString(),
        ]);
    }

    public function edit(Staff $staff): Response
    {
        $staff->load('user.stores');

        return Inertia::render('Admin/Staff/Form', [
            'staff' => $staff,
            'roles' => $this->assignableRoles(),
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

    // Position management
    public function positionsIndex(): Response
    {
        $positions = Position::ordered()->get(['id', 'name', 'description', 'is_active', 'sort_order']);

        return Inertia::render('Admin/Staff/Positions', [
            'positions' => $positions,
        ]);
    }

    public function positionStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:positions,name'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        Position::create($data);

        return back()->with('success', 'Position created.');
    }

    public function positionUpdate(Request $request, Position $position)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:positions,name,'.$position->id],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $position->update($data);

        return back()->with('success', 'Position updated.');
    }

    public function positionDestroy(Request $request, Position $position)
    {
        $position->delete();

        return back()->with('success', 'Position deleted.');
    }

    protected function assignableRoles(): Collection
    {
        return Role::query()
            ->where('slug', '!=', 'owner')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function validated(Request $request, int $ignoreId = 0): array
    {
        $ignoreUserId = $ignoreId ? Staff::find($ignoreId)?->user_id : null;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => [
                'required', 'email', 'max:255',
                'unique:staff,email,'.$ignoreId,
                Rule::unique('users', 'email')->ignore($ignoreUserId),
            ],
            'role_id' => ['required', 'exists:roles,id'],
            'password' => $ignoreId === 0
                ? ['required', 'confirmed', Rules\Password::defaults()]
                : ['sometimes', 'nullable', 'confirmed', Rules\Password::defaults()],
            'date_joined' => ['nullable', 'date'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'housing_allowance' => ['nullable', 'numeric', 'min:0'],
            'transport_allowance' => ['nullable', 'numeric', 'min:0'],
            'other_allowance' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ]);
    }
}
