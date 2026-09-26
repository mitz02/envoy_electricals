<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectExpenseController extends Controller
{
    public function __construct(protected ProjectService $projects) {}

    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'expense_type' => ['required', 'in:'.implode(',', [ProjectExpense::TYPE_LABOUR, ProjectExpense::TYPE_TRANSPORT, ProjectExpense::TYPE_OTHER])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'payee' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->projects->addExpense($project, $data, (int) $request->user()->id);

        return back()->with('success', 'Project expense recorded.');
    }

    public function destroy(Request $request, Project $project, ProjectExpense $expense): RedirectResponse
    {
        $this->projects->deleteExpense($project, $expense, (int) $request->user()->id);

        return back()->with('success', 'Project expense removed.');
    }
}
