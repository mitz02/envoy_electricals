<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMaterial;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProjectMaterialController extends Controller
{
    public function __construct(protected ProjectService $projects) {}

    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'issue_now' => ['nullable', 'boolean'],
        ]);

        $this->projects->addMaterial($project, $data, (int) $request->user()->id);

        return back()->with('success', 'Material added to project.');
    }

    public function issue(Request $request, Project $project, ProjectMaterial $material): RedirectResponse
    {
        try {
            $this->projects->issueMaterial($project, $material, (int) $request->user()->id);
        } catch (ValidationException $e) {
            return back()->withErrors($e->validator)->with('error', 'Material could not be issued.');
        }

        return back()->with('success', 'Material issued to inventory.');
    }

    public function destroy(Request $request, Project $project, ProjectMaterial $material): RedirectResponse
    {
        if ($material->issued_to_inventory) {
            $message = 'Material removed and stock returned to inventory.';
        } else {
            $message = 'Material removed from project.';
        }

        $this->projects->removeMaterial($project, $material, (int) $request->user()->id);

        return back()->with('success', $message);
    }
}
