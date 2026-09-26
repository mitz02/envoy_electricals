<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Services\AuditLogger;
use App\Services\ReferenceGenerator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    public function index(Request $request): Response
    {
        $assets = Asset::query()
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('serial_number', 'like', "%{$s}%")
                    ->orWhere('location', 'like', "%{$s}%")
                    ->orWhere('category', 'like', "%{$s}%")
                    ->orWhere('ref_id', 'like', "%{$s}%");
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->category, fn ($q, $c) => $q->where('category', $c))
            ->withCount('maintenances')
            ->latest('purchase_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total' => Asset::count(),
            'active' => Asset::active()->count(),
            'disposed' => Asset::where('status', Asset::STATUS_DISPOSED)->count(),
            'value' => round((float) Asset::where('status', '!=', Asset::STATUS_DISPOSED)->sum('current_value'), 2),
        ];

        return Inertia::render('Admin/Assets/Index', [
            'assets' => $assets,
            'summary' => $summary,
            'statuses' => Asset::STATUSES,
            'categories' => Asset::distinct()->orderBy('category')->pluck('category')->filter()->values(),
            'filters' => $request->only(['search', 'status', 'category']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Assets/Form', [
            'asset' => null,
            'statuses' => Asset::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $asset = Asset::create([
            ...$data,
            'ref_id' => ReferenceGenerator::generate('asset'),
        ]);

        AuditLogger::log('created', 'asset', $asset->id, "Created asset {$asset->ref_id}: {$asset->name}");

        return redirect()->route('admin.assets.show', $asset->id)->with('success', 'Asset added to the register.');
    }

    public function show(Asset $asset): Response
    {
        return Inertia::render('Admin/Assets/Show', [
            'asset' => $asset->loadCount('maintenances'),
            'maintenances' => $asset->maintenances()->latest('maintenance_date')->latest('id')->get(),
            'statuses' => Asset::STATUSES,
        ]);
    }

    public function edit(Asset $asset): Response
    {
        return Inertia::render('Admin/Assets/Form', [
            'asset' => $asset,
            'statuses' => Asset::STATUSES,
        ]);
    }

    public function update(Request $request, Asset $asset)
    {
        $data = $this->validated($request);

        $asset->update($data);

        AuditLogger::log('updated', 'asset', $asset->id, "Updated asset {$asset->ref_id}: {$asset->name}");

        return redirect()->route('admin.assets.show', $asset->id)->with('success', 'Asset updated.');
    }

    public function destroy(Request $request, Asset $asset)
    {
        $ref = $asset->ref_id;
        $asset->delete();

        AuditLogger::log('deleted', 'asset', $asset->id, "Removed asset {$ref} from the register");

        return redirect()->route('admin.assets.index')->with('success', 'Asset removed.');
    }

    public function storeMaintenance(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'maintenance_date' => ['required', 'date'],
            'type' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'cost' => ['required', 'numeric', 'min:0'],
        ]);

        $maintenance = $asset->maintenances()->create($data);

        AuditLogger::log('maintenance', 'asset', $asset->id, "Recorded {$data['type']} maintenance on {$asset->ref_id} (₦{$data['cost']})");

        return redirect()->route('admin.assets.show', $asset->id)->with('success', 'Maintenance record added.');
    }

    public function destroyMaintenance(Request $request, Asset $asset, AssetMaintenance $maintenance)
    {
        $maintenance->delete();

        AuditLogger::log('deleted', 'asset_maintenance', $maintenance->id, "Removed maintenance record from {$asset->ref_id}");

        return redirect()->route('admin.assets.show', $asset->id)->with('success', 'Maintenance record removed.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['required', 'numeric', 'min:0'],
            'current_value' => ['required', 'numeric', 'min:0'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'condition' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive,disposed'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
