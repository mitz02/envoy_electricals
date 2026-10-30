<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InverterCapacity;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InverterCapacityController extends Controller
{
    public function index(Request $request): Response
    {
        $capacities = InverterCapacity::query()
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('label', 'like', "%{$s}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('label')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/InverterCapacities/Index', [
            'capacities' => $capacities,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/InverterCapacities/Form', [
            'capacity' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'label' => ['required', 'string', 'max:255', 'unique:inverter_capacities,label'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $capacity = InverterCapacity::create([
            'name' => $data['name'],
            'label' => $data['label'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        AuditLogger::log('created', 'inverter_capacity', $capacity->id, "Created inverter capacity {$capacity->label}");

        return redirect()->route('admin.inverter-capacities.index')->with('success', 'Inverter capacity created.');
    }

    public function edit(InverterCapacity $inverterCapacity): Response
    {
        return Inertia::render('Admin/InverterCapacities/Form', [
            'capacity' => $inverterCapacity,
        ]);
    }

    public function update(Request $request, InverterCapacity $inverterCapacity): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'label' => ['required', 'string', 'max:255', 'unique:inverter_capacities,label,'.$inverterCapacity->id],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $inverterCapacity->update([
            'name' => $data['name'],
            'label' => $data['label'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        AuditLogger::log('updated', 'inverter_capacity', $inverterCapacity->id, "Updated inverter capacity {$inverterCapacity->label}");

        return redirect()->route('admin.inverter-capacities.index')->with('success', 'Inverter capacity updated.');
    }

    public function destroy(Request $request, InverterCapacity $inverterCapacity): RedirectResponse
    {
        $label = $inverterCapacity->label;
        $inverterCapacity->delete();

        AuditLogger::log('deleted', 'inverter_capacity', $inverterCapacity->id, "Deleted inverter capacity {$label}");

        return redirect()->route('admin.inverter-capacities.index')->with('success', 'Inverter capacity deleted.');
    }

    public function quickCreate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'label' => ['required', 'string', 'max:255', 'unique:inverter_capacities,label'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $capacity = InverterCapacity::create([
            'name' => $data['name'],
            'label' => $data['label'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        AuditLogger::log('created', 'inverter_capacity', $capacity->id, "Created inverter capacity {$capacity->label} (quick)");

        return response()->json([
            'success' => true,
            'capacity' => $capacity->only(['id', 'name', 'label', 'sort_order', 'is_active']),
        ]);
    }
}
