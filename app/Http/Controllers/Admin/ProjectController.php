<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Services\AuditLogger;
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
            ->with(['customer' => fn ($q) => $q->withoutGlobalScope('store'), 'assignedUser'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('ref_id', 'like', "%{$s}%")
                    ->orWhere('location', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($q) => $q->withoutGlobalScope('store')->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->customer_id, fn ($q, $c) => $q->where('customer_id', $c))
            ->latest('start_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $all = Project::query();

        return Inertia::render('Admin/Projects/Index', [
            'projects' => $projects,
            'summary' => [
                'active_projects' => Project::whereIn('status', Project::ACTIVE_STATUSES)->count(),
                'total_projects' => (clone $all)->count(),
                'projects_revenue' => round((float) (clone $all)->sum('amount_received'), 2),
                'projects_cost' => round((float) (clone $all)->sum('project_cost'), 2),
                'projects_profit' => round((float) (clone $all)->sum('gross_profit'), 2),
                'outstanding' => round((float) (clone $all)->where('balance', '>', 0)->sum('balance'), 2),
            ],
            'customers' => Customer::withoutGlobalScope('store')->orderBy('name')->get(['id', 'name', 'address']),
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

    public function publish(Request $request, Project $project): RedirectResponse
    {
        $project->update(['published' => $request->boolean('published')]);

        AuditLogger::log('project_publish', 'project', $project->id,
            ($project->published ? 'Published' : 'Unpublished')." project {$project->ref_id}");

        return back()->with('success', $project->published ? 'Project published to website.' : 'Project moved to draft.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->projects->destroy($project, (int) $request->user()->id);

        return redirect()->route('admin.projects.index')->with('success', 'Project deleted.');
    }

    public function clearManualCosts(Request $request, Project $project): RedirectResponse
    {
        $project->update([
            'manual_material_cost' => null,
            'manual_labour_cost' => null,
            'manual_transport_cost' => null,
            'manual_other_cost' => null,
            'manual_project_cost' => null,
        ]);

        $this->projects->recompute($project);

        return back()->with('success', 'Manual cost overrides cleared. Reverted to auto-calculated values.');
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
            'published' => ['boolean'],
            'manual_material_cost' => ['nullable', 'numeric'],
            'manual_labour_cost' => ['nullable', 'numeric'],
            'manual_transport_cost' => ['nullable', 'numeric'],
            'manual_other_cost' => ['nullable', 'numeric'],
            'manual_project_cost' => ['nullable', 'numeric'],
        ]);
    }

    protected function formData(?Project $project, string $view): Response
    {
        return Inertia::render($view, [
            'project' => $project,
            'customers' => Customer::withoutGlobalScope('store')->orderBy('name')->get(['id', 'name', 'address']),
            'users' => User::with('role')->orderBy('name')->get(['id', 'name', 'role_id']),
            'statuses' => Project::STATUSES,
        ]);
    }
}
