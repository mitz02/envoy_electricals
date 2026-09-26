<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function __construct(protected ProjectService $projects) {}

    public function index(Request $request): Response
    {
        $projects = Project::query()
            ->with(['customer', 'assignedUser'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('ref_id', 'like', "%{$s}%")
                    ->orWhere('location', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->customer_id, fn ($q, $c) => $q->where('customer_id', $c))
            ->latest('start_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $active = Project::whereIn('status', Project::ACTIVE_STATUSES);

        return Inertia::render('Admin/Projects/Index', [
            'projects' => $projects,
            'summary' => [
                'active_projects' => (clone $active)->count(),
                'projects_revenue' => round((float) (clone $active)->sum('amount_received'), 2),
                'projects_cost' => round((float) (clone $active)->sum('project_cost'), 2),
                'projects_profit' => round((float) (clone $active)->sum('gross_profit'), 2),
                'outstanding' => round((float) (clone $active)->where('balance', '>', 0)->sum('balance'), 2),
            ],
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'address']),
            'filters' => $request->only(['search', 'status', 'customer_id']),
            'statuses' => Project::STATUSES,
        ]);
    }

    public function create(): Response
    {
        return $this->formData(project: null, view: 'Admin/Projects/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $project = $this->projects->create($data, (int) $request->user()->id);

        return redirect()->route('admin.projects.show', $project)->with('success', 'Project created.');
    }

    public function show(Project $project): Response
    {
        $project->load([
            'customer',
            'assignedUser',
            'technicianUser',
            'materials.product',
            'payments',
            'expenses',
            'media',
        ]);

        return Inertia::render('Admin/Projects/Show', [
            'project' => $project,
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku', 'current_quantity', 'average_cost']),
            'statuses' => Project::STATUSES,
        ]);
    }

    public function edit(Project $project): Response
    {
        return $this->formData(project: $project, view: 'Admin/Projects/Form');
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $this->validated($request);

        $this->projects->update($project, $data, (int) $request->user()->id);

        return redirect()->route('admin.projects.show', $project)->with('success', 'Project updated.');
    }

    public function status(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', Project::STATUSES)]]);

        $this->projects->updateStatus($project, $data['status'], (int) $request->user()->id);

        return back()->with('success', "Project marked as {$data['status']}.");
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->projects->destroy($project, (int) $request->user()->id);

        return redirect()->route('admin.projects.index')->with('success', 'Project deleted.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_address' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'contract_value' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:'.implode(',', Project::STATUSES)],
            'start_date' => ['nullable', 'date'],
            'expected_completion_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    protected function formData(?Project $project, string $view): Response
    {
        return Inertia::render($view, [
            'project' => $project,
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'address']),
            'users' => User::with('role')->orderBy('name')->get(['id', 'name', 'role_id']),
            'statuses' => Project::STATUSES,
        ]);
    }
}
