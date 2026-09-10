<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\SolarCalculation;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SolarLeadController extends Controller
{
    public function index(Request $request): \Inertia\Response
    {
        $tab = $request->get('tab', 'calculations');

        $calculations = SolarCalculation::query()
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('customer_name', 'like', "%{$s}%")
                    ->orWhere('customer_phone', 'like', "%{$s}%")
                    ->orWhere('customer_email', 'like', "%{$s}%")
                    ->orWhere('location', 'like', "%{$s}%")
                    ->orWhere('ref_id', 'like', "%{$s}%");
            }))
            ->when($request->status, fn ($q, $s) => $q->where('lead_status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $quotations = Quotation::query()
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('customer_name', 'like', "%{$s}%")
                    ->orWhere('customer_phone', 'like', "%{$s}%")
                    ->orWhere('customer_email', 'like', "%{$s}%")
                    ->orWhere('location', 'like', "%{$s}%")
                    ->orWhere('ref_id', 'like', "%{$s}%");
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/SolarLeads/Index', [
            'tab' => $tab,
            'calculations' => $calculations,
            'quotations' => $quotations,
            'calc_statuses' => ['new', 'contacted', 'quoted', 'approved', 'installation', 'completed', 'lost'],
            'quotation_statuses' => ['new', 'contacted', 'quoted', 'approved', 'declined', 'converted'],
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(Request $request, SolarCalculation $calculation): \Inertia\Response
    {
        $calculation->load('recommendedPackage');

        return Inertia::render('Admin/SolarLeads/Show', [
            'record' => $calculation,
            'type' => 'calculation',
            'statuses' => ['new', 'contacted', 'quoted', 'approved', 'installation', 'completed', 'lost'],
        ]);
    }

    public function showQuotation(Quotation $quotation): \Inertia\Response
    {
        $quotation->load('solarPackage');

        return Inertia::render('Admin/SolarLeads/Show', [
            'record' => $quotation,
            'type' => 'quotation',
            'statuses' => ['new', 'contacted', 'quoted', 'approved', 'declined', 'converted'],
        ]);
    }

    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:calculation,quotation'],
            'id' => ['required', 'int'],
            'status' => ['required', 'string'],
        ]);

        if ($validated['type'] === 'calculation') {
            $record = SolarCalculation::findOrFail($validated['id']);
            $record->update(['lead_status' => $validated['status']]);
            $label = $validated['status'];
        } else {
            $record = Quotation::findOrFail($validated['id']);
            $record->update(['status' => $validated['status']]);
            $label = $validated['status'];
        }

        AuditLogger::log('updated', $validated['type'], $record->id, "Marked {$record->ref_id} as {$label}");

        return redirect()->back()->with('success', "Lead {$record->ref_id} updated to {$label}.");
    }

    public function destroy(Request $request, SolarCalculation $calculation)
    {
        $ref = $calculation->ref_id;
        $calculation->delete();

        AuditLogger::log('deleted', 'solar_calculation', $calculation->id, "Removed solar lead {$ref}");

        return redirect()->route('admin.solar-leads.index')->with('success', 'Solar lead removed.');
    }

    public function destroyQuotation(Quotation $quotation)
    {
        $ref = $quotation->ref_id;
        $quotation->delete();

        AuditLogger::log('deleted', 'quotation', $quotation->id, "Removed quotation {$ref}");

        return redirect()->route('admin.solar-leads.index')->with('success', 'Quotation removed.');
    }
}