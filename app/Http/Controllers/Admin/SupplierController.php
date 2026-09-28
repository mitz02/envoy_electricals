<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\ReferenceGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $suppliers = Supplier::query()
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            }))
            ->withSum(['purchases as total_purchases' => fn ($q) => $q->where('status', 'completed')], 'total')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $supplier = Supplier::create([...$data, 'ref_id' => ReferenceGenerator::generate('supplier')]);

        return redirect()
            ->route('admin.suppliers.index', ['search' => $supplier->name])
            ->with('success', 'Supplier added.');
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $supplier->update($data);

        return redirect()
            ->route('admin.suppliers.index', ['search' => $supplier->name])
            ->with('success', 'Supplier updated.');
    }

    public function show(Supplier $supplier): Response
    {
        $supplier->load(['purchases' => fn ($q) => $q->latest('purchase_date')->take(10)]);

        return Inertia::render('Admin/Suppliers/Show', [
            'supplier' => $supplier,
        ]);
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return back()->with('success', 'Supplier deleted.');
    }

    public function quickCreate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:255'],
        ]);

        // Create supplier without auto-assigning store (for quick inline creation)
        $supplier = new Supplier($data);
        $supplier->skipStoreAutoAssign = true;
        $supplier->ref_id = ReferenceGenerator::generate('supplier');
        $supplier->save();

        return response()->json([
            'success' => true,
            'supplier' => $supplier,
        ]);
    }
}
