<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Store;
use App\Services\AuditLogger;
use App\Services\ReferenceGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $storeId = session('admin_store_id');
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'category_id' => $request->input('category_id'),
            'status' => $request->input('status'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ];

        $expenses = Expense::query()
            ->with(['category', 'store'])
            ->when($filters['search'], fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('ref_id', 'like', "%{$search}%")
                    ->orWhere('paid_to', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhere('remarks', 'like', "%{$search}%");
            }))
            ->when($filters['category_id'], fn ($q, $categoryId) => $q->where('expense_category_id', $categoryId))
            ->when($filters['status'], fn ($q, $status) => $q->where('status', $status))
            ->when($filters['from'], fn ($q, $from) => $q->whereDate('expense_date', '>=', $from))
            ->when($filters['to'], fn ($q, $to) => $q->whereDate('expense_date', '<=', $to))
            ->latest('expense_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $monthTotal = (float) Expense::query()
            ->where('status', 'recorded')
            ->whereDate('expense_date', '>=', now()->startOfMonth())
            ->sum('amount');

        return Inertia::render('Admin/Expenses/Index', [
            'expenses' => $expenses,
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'filters' => $filters,
            'month_total' => $monthTotal,
            'result_count' => $expenses->total(),
            'selectedStoreId' => $storeId,
        ]);
    }

    public function create(Request $request): Response
    {
        $storeId = $this->currentStoreId($request);

        return Inertia::render('Admin/Expenses/Form', [
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'expense' => null,
            'store' => $storeId ? Store::find($storeId) : null,
        ]);
    }

    public function edit(Expense $expense): Response
    {
        return Inertia::render('Admin/Expenses/Form', [
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'expense' => $expense->load('store'),
            'store' => $expense->store,
        ]);
    }

    public function store(Request $request): RedirectResponse
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

        $storeId = $this->currentStoreId($request);

        $expense = Expense::create([
            ...$data,
            'store_id' => $storeId,
            'ref_id' => ReferenceGenerator::generate('expense'),
            'status' => 'recorded',
            'created_by' => $request->user()->id,
        ]);

        AuditLogger::log('created', 'expense', $expense->id, "Recorded expense ₦{$expense->amount}: {$expense->description}");

        return redirect()->route('admin.expenses.index')->with('success', 'Expense recorded.');
    }

    public function update(Request $request, Expense $expense): RedirectResponse
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

    public function destroy(Expense $expense): RedirectResponse
    {
        $ref = $expense->ref_id;
        $expense->delete();
        AuditLogger::log('deleted', 'expense', $expense->id, "Deleted expense {$ref}");

        return back()->with('success', 'Expense deleted.');
    }

    private function currentStoreId(Request $request): ?int
    {
        $storeId = session('admin_store_id')
            ?? $request->user()?->store_id
            ?? Store::where('is_default', true)->value('id');

        return $storeId ? (int) $storeId : null;
    }
}
