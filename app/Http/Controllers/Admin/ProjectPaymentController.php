<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectPayment;
use App\Services\ProjectService;
use Illuminate\Http\Request;

class ProjectPaymentController extends Controller
{
    public const METHODS = ['cash', 'bank', 'pos', 'cheque', 'online'];

    public function __construct(protected ProjectService $projects) {}

    public function store(Request $request, Project $project): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:' . implode(',', self::METHODS)],
            'reference' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->projects->recordPayment($project, $data, (int) $request->user()->id);

        return back()->with('success', 'Project payment recorded.');
    }

    public function destroy(Request $request, Project $project, ProjectPayment $payment): \Illuminate\Http\RedirectResponse
    {
        $this->projects->deletePayment($project, $payment, (int) $request->user()->id);

        return back()->with('success', 'Project payment removed.');
    }
}