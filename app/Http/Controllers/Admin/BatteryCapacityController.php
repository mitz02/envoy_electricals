<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BatteryCapacity;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BatteryCapacityController extends Controller
{
    public function index(Request $request): Response
    {
        $capacities = BatteryCapacity::query()
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('label', 'like', "%{$s}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('label')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/BatteryCapacities/Index', [
            'capacities' => $capacities,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/BatteryCapacities/Form', [
            'capacity' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'label' => ['required', 'string', 'max:255', 'unique:battery_capacities,label'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $capacity = BatteryCapacity::create([
            'name' => $data['name'],
            'label' => $data['label'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        AuditLogger::log('created', 'battery_capacity', $capacity->id, "Created battery capacity {$capacity->label}");

        return redirect()->route('admin.battery-capacities.index')->with('success', 'Battery capacity created.');
    }

    public function edit(BatteryCapacity $batteryCapacity): Response
    {
        return Inertia::render('Admin/BatteryCapacities/Form', [
            'capacity' => $batteryCapacity,
        ]);
    }

    public function update(Request $request, BatteryCapacity $batteryCapacity): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'label' => ['required', 'string', 'max:255', 'unique:battery_capacities,label,'.$batteryCapacity->id],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $batteryCapacity->update([
            'name' => $data['name'],
            'label' => $data['label'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        AuditLogger::log('updated', 'battery_capacity', $batteryCapacity->id, "Updated battery capacity {$batteryCapacity->label}");

        return redirect()->route('admin.battery-capacities.index')->with('success', 'Battery capacity updated.');
    }

    public function destroy(Request $request, BatteryCapacity $batteryCapacity): RedirectResponse
    {
        $label = $batteryCapacity->label;
        $batteryCapacity->delete();

        AuditLogger::log('deleted', 'battery_capacity', $batteryCapacity->id, "Deleted battery capacity {$label}");

        return redirect()->route('admin.battery-capacities.index')->with('success', 'Battery capacity deleted.');
    }

    public function quickCreate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'label' => ['required', 'string', 'max:255', 'unique:battery_capacities,label'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $capacity = BatteryCapacity::create([
            'name' => $data['name'],
            'label' => $data['label'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        AuditLogger::log('created', 'battery_capacity', $capacity->id, "Created battery capacity {$capacity->label} (quick)");

        return response()->json([
            'success' => true,
            'capacity' => $capacity->only(['id', 'name', 'label', 'sort_order', 'is_active']),
        ]);
    }
}
