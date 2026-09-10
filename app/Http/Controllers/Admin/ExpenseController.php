<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\AuditLogger;
use App\Services\ReferenceGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index(Request $request): \Inertia\Response
    {
        $expenses = Expense::query()
            ->with(['category'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                    ->orWhere('ref_id', 'like', "%{$s}%")
                    ->orWhere('paid_to', 'like', "%{$s}%");
            }))
            ->when($request->category_id, fn ($q, $c) => $q->where('expense_category_id', $c))
            ->when($request->from, fn ($q, $d) => $q->whereDate('expense_date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('expense_date', '<=', $d))
            ->latest('expense_date')
            ->paginate(15)
            ->withQueryString();

        $monthTotal = (float) Expense::where('status', 'recorded')
            ->whereDate('expense_date', '>=', now()->startOfMonth())
            ->sum('amount');

        return Inertia::render('Admin/Expenses/Index', [
            'expenses' => $expenses,
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'filters' => $request->only(['search', 'category_id', 'from', 'to']),
            'month_total' => $monthTotal,
        ]);
    }

    public function create(): \Inertia\Response
    {
        return Inertia::render('Admin/Expenses/Form', [
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'expense' => null,
        ]);
    }

    public function edit(Expense $expense): \Inertia\Response
    {
        return Inertia::render('Admin/Expenses/Form', [
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'expense' => $expense,
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'expense_date' => ['required', 'date'],
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'paid_to' => ['nullable', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'staff_id' => ['nullable', 'exists:staff,id'],
            'remarks' => ['nullable', 'string'],
        ]);

        $expense = Expense::create([
            ...$data,
            'ref_id' => ReferenceGenerator::generate('expense'),
            'status' => 'recorded',
            'created_by' => $request->user()->id,
        ]);

        AuditLogger::log('created', 'expense', $expense->id, "Recorded expense ₦{$expense->amount}: {$expense->description}");

        return redirect()->route('admin.expenses.index')->with('success', 'Expense recorded.');
    }

    public function update(Request $request, Expense $expense): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'expense_date' => ['required', 'date'],
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'paid_to' => ['nullable', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'staff_id' => ['nullable', 'exists:staff,id'],
            'remarks' => ['nullable', 'string'],
        ]);

        $expense->update($data);

        AuditLogger::log('updated', 'expense', $expense->id, "Updated expense {$expense->ref_id}");

        return redirect()->route('admin.expenses.index')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense): \Illuminate\Http\RedirectResponse
    {
        $ref = $expense->ref_id;
        $expense->delete();
        AuditLogger::log('deleted', 'expense', $expense->id, "Deleted expense {$ref}");

        return back()->with('success', 'Expense deleted.');
    }
}