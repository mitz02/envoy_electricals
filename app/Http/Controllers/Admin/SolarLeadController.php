<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\QuotationMail;
use App\Models\Quotation;
use App\Models\SolarCalculation;
use App\Models\SolarPackage;
use App\Services\AuditLogger;
use App\Services\ReferenceGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class SolarLeadController extends Controller
{
    public function index(Request $request): Response
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

    public function show(Request $request, SolarCalculation $calculation): Response
    {
        $calculation->load('recommendedPackage');

        return Inertia::render('Admin/SolarLeads/Show', [
            'record' => $calculation,
            'type' => 'calculation',
            'statuses' => ['new', 'contacted', 'quoted', 'approved', 'installation', 'completed', 'lost'],
            'packages' => SolarPackage::where('is_visible_online', true)->get(['id', 'name', 'package_price']),
        ]);
    }

    public function showQuotation(Quotation $quotation): Response
    {
        $quotation->load('solarPackage');

        return Inertia::render('Admin/SolarLeads/Show', [
            'record' => $quotation,
            'type' => 'quotation',
            'statuses' => ['new', 'contacted', 'quoted', 'approved', 'declined', 'converted'],
        ]);
    }

    public function createQuotation(Request $request, SolarCalculation $calculation): RedirectResponse
    {
        $data = $request->validate([
            'solar_package_id' => ['nullable', 'exists:solar_packages,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'additional_logistics' => ['nullable', 'numeric', 'min:0'],
        ]);

        $quotation = Quotation::create([
            'ref_id' => ReferenceGenerator::generate('quotation'),
            'customer_id' => null,
            'customer_name' => $calculation->customer_name,
            'customer_phone' => $calculation->customer_phone,
            'customer_email' => $calculation->customer_email,
            'location' => $calculation->location,
            'appliances_json' => $calculation->appliances_json,
            'recommended_system' => $calculation->recommended_inverter,
            'solar_package_id' => $data['solar_package_id'] ?? null,
            'estimated_price' => $calculation->estimated_price,
            'additional_logistics' => $data['additional_logistics'] ?? 0,
            'status' => 'quoted',
            'notes' => $data['notes'],
        ]);

        $calculation->update(['lead_status' => 'quoted']);

        AuditLogger::log('created', 'quotation', $quotation->id, "Created quotation {$quotation->ref_id} from calculation {$calculation->ref_id}");

        return redirect()->route('admin.solar-leads.quotations.show', $quotation)
            ->with('success', "Quotation {$quotation->ref_id} created successfully.");
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

    public function createQuotationManual(): Response
    {
        $packages = SolarPackage::where('is_visible_online', true)
            ->where('availability', 'available')
            ->get(['id', 'name', 'package_price', 'installation_cost', 'inverter_capacity', 'estimated_load_capacity']);

        return Inertia::render('Admin/SolarLeads/CreateQuotation', [
            'packages' => $packages,
        ]);
    }

    public function storeQuotationManual(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'solar_package_id' => ['required', 'exists:solar_packages,id'],
            'estimated_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'additional_logistics' => ['nullable', 'numeric', 'min:0'],
        ]);

        $package = SolarPackage::find($data['solar_package_id']);

        $quotation = Quotation::create([
            'ref_id' => ReferenceGenerator::generate('quotation'),
            'customer_id' => null,
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'customer_email' => $data['customer_email'],
            'location' => $data['location'],
            'appliances_json' => null,
            'recommended_system' => $package->inverter_capacity ?? 'Custom System',
            'solar_package_id' => $data['solar_package_id'],
            'estimated_price' => $data['estimated_price'] ?? ($package->package_price + $package->installation_cost),
            'status' => 'quoted',
            'notes' => $data['notes'],
        ]);

        AuditLogger::log('created', 'quotation', $quotation->id, "Created manual quotation {$quotation->ref_id} for {$data['customer_name']}");

        return redirect()->route('admin.solar-leads.quotations.show', $quotation)
            ->with('success', "Quotation {$quotation->ref_id} created successfully.");
    }

    public function sendQuotation(Request $request, Quotation $quotation): RedirectResponse
    {
        $quotation->load('solarPackage');

        // Update status to quoted if not already
        if ($quotation->status !== 'quoted') {
            $quotation->update(['status' => 'quoted']);
        }

        // Send email with quotation
        try {
            Mail::to($quotation->customer_email)->send(new QuotationMail($quotation));
            $quotation->update(['notes' => ($quotation->notes ?? '')."\n\n[Quotation sent via email on ".now()->format('Y-m-d H:i').']']);

            AuditLogger::log('updated', 'quotation', $quotation->id, "Quotation {$quotation->ref_id} sent to {$quotation->customer_email}");

            return redirect()->back()->with('success', "Quotation sent to {$quotation->customer_email}.");
        } catch (\Exception $e) {
            \Log::error('Failed to send quotation email', [
                'quotation_id' => $quotation->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to send email: '.$e->getMessage());
        }
    }
}
