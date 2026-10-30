<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CustomQuotationMail;
use App\Models\Customer;
use App\Models\CustomQuotation;
use App\Models\CustomQuotationItem;
use App\Models\Project;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\ReferenceGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CustomQuotationController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->get('status');
        $search = $request->get('search');
        $jobType = $request->get('job_type');

        $quotations = CustomQuotation::query()
            ->withCount('items')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($jobType, fn ($q) => $q->where('job_type', $jobType))
            ->when($search, fn ($q) => $q->search($search))
            ->latest('quotation_date')
            ->paginate(15)
            ->withQueryString();

        $jobTypes = CustomQuotation::distinct()->pluck('job_type')->filter()->values();

        return Inertia::render('Admin/CustomQuotations/Index', [
            'quotations' => $quotations,
            'filters' => [
                'status' => $status,
                'search' => $search,
                'job_type' => $jobType,
            ],
            'jobTypes' => $jobTypes,
            'statuses' => ['draft', 'sent', 'viewed', 'accepted', 'rejected', 'expired', 'converted'],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/CustomQuotations/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:255'],
            'customer_address' => ['nullable', 'string'],
            'job_description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $quotation = DB::transaction(function () use ($data) {
            $refId = CustomQuotation::generateRefId();

            $quotation = CustomQuotation::create([
                'ref_id' => $refId,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'],
                'customer_address' => $data['customer_address'],
                'customer_company' => null,
                'quotation_date' => now()->toDateString(),
                'valid_until' => now()->addDays(30)->toDateString(),
                'job_type' => 'Custom Service',
                'title' => $data['job_description'] ? Str::limit($data['job_description'], 80) : 'Custom Quotation',
                'description' => $data['job_description'],
                'notes' => $data['notes'] ?? null,
                'discount' => 0,
                'tax' => 0,
                'other_charges' => 0,
                'status' => 'draft',
            ]);

            foreach ($data['items'] as $index => $item) {
                $total = CustomQuotationItem::calculateTotal($item['quantity'], $item['unit_price']);
                CustomQuotationItem::create([
                    'custom_quotation_id' => $quotation->id,
                    'item' => $item['item'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'total' => $total,
                    'sort_order' => $index,
                ]);
            }

            $quotation->recalculateTotals();

            return $quotation;
        });

        AuditLogger::log('created', 'custom_quotation', $quotation->id, "Created custom quotation {$quotation->ref_id}");

        return redirect()->route('admin.custom-quotations.show', $quotation)
            ->with('success', "Quotation {$quotation->ref_id} created successfully.");
    }

    public function show(CustomQuotation $customQuotation): Response
    {
        $customQuotation->load('items');

        return Inertia::render('Admin/CustomQuotations/Show', [
            'quotation' => $customQuotation,
            'statuses' => ['draft', 'sent', 'viewed', 'accepted', 'rejected', 'expired', 'converted'],
        ]);
    }

    public function edit(CustomQuotation $customQuotation): Response
    {
        $customQuotation->load('items');

        return Inertia::render('Admin/CustomQuotations/Edit', [
            'quotation' => $customQuotation,
        ]);
    }

    public function update(Request $request, CustomQuotation $customQuotation): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:255'],
            'customer_address' => ['nullable', 'string'],
            'job_description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($customQuotation, $data) {
            $customQuotation->update([
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'],
                'customer_address' => $data['customer_address'],
                'description' => $data['job_description'],
                'notes' => $data['notes'] ?? null,
                'title' => $data['job_description'] ? Str::limit($data['job_description'], 80) : 'Custom Quotation',
            ]);

            $customQuotation->items()->delete();

            foreach ($data['items'] as $index => $item) {
                $total = CustomQuotationItem::calculateTotal($item['quantity'], $item['unit_price']);
                CustomQuotationItem::create([
                    'custom_quotation_id' => $customQuotation->id,
                    'item' => $item['item'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'total' => $total,
                    'sort_order' => $index,
                ]);
            }

            $customQuotation->recalculateTotals();
        });

        AuditLogger::log('updated', 'custom_quotation', $customQuotation->id, "Updated custom quotation {$customQuotation->ref_id}");

        return redirect()->route('admin.custom-quotations.show', $customQuotation)
            ->with('success', "Quotation {$customQuotation->ref_id} updated successfully.");
    }

    public function updateStatus(Request $request, CustomQuotation $customQuotation): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:draft,sent,viewed,accepted,rejected,expired,converted'],
        ]);

        $customQuotation->update(['status' => $data['status']]);

        AuditLogger::log('updated', 'custom_quotation', $customQuotation->id, "Status changed to {$data['status']} for {$customQuotation->ref_id}");

        return redirect()->back()->with('success', "Status updated to {$customQuotation->getStatusLabel()}.");
    }

    public function send(CustomQuotation $customQuotation): RedirectResponse
    {
        if (! $customQuotation->customer_email) {
            return redirect()->back()->with('error', 'Customer email is required to send quotation.');
        }

        try {
            Mail::to($customQuotation->customer_email)->send(new CustomQuotationMail($customQuotation));

            if ($customQuotation->status === 'draft') {
                $customQuotation->update(['status' => 'sent']);
            }

            AuditLogger::log('sent', 'custom_quotation', $customQuotation->id, "Sent quotation {$customQuotation->ref_id} to {$customQuotation->customer_email}");

            return redirect()->back()->with('success', "Quotation sent to {$customQuotation->customer_email}.");
        } catch (\Exception $e) {
            \Log::error('Failed to send custom quotation email', [
                'quotation_id' => $customQuotation->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to send email: '.$e->getMessage());
        }
    }

    public function downloadPdf(CustomQuotation $customQuotation)
    {
        $customQuotation->load('items');

        $settings = $this->getSettings();

        $pdf = Pdf::loadView('pdf.quote-invoice', [
            'quotation' => $customQuotation,
            'company' => [
                'name' => $settings['business.name'] ?? 'Envoy Electricals',
                'address' => $settings['business.address'] ?? '',
                'email' => $settings['business.email'] ?? 'envoyelectricals@gmail.com',
                'phone' => $settings['business.phone'] ?? '+234 809 708 9259',
                'bank_name' => $settings['bank.bank_name'] ?? 'Wema Bank',
                'account_name' => $settings['bank.account_name'] ?? 'Envoy Electricals',
                'account_number' => $settings['bank.account_number'] ?? '0126278482',
                'footer' => $settings['business.footer'] ?? 'powering your world with innovative solutions',
            ],
        ])->setPaper('A4');

        return $pdf->download("Quotation-{$customQuotation->ref_id}.pdf")
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    protected function getSettings(): array
    {
        return Setting::whereIn('key', [
            'business.name',
            'business.address',
            'business.email',
            'business.phone',
            'business.footer',
            'bank.bank_name',
            'bank.account_name',
            'bank.account_number',
        ])->pluck('value', 'key')->toArray();
    }

    public function previewPdf(CustomQuotation $customQuotation)
    {
        $customQuotation->load('items');

        return Inertia::render('Admin/CustomQuotations/Preview', [
            'quotation' => $customQuotation,
        ]);
    }

    public function convertToProject(CustomQuotation $customQuotation): RedirectResponse
    {
        $customQuotation->load('items');

        $project = DB::transaction(function () use ($customQuotation) {
            $customer = null;
            if (! empty($customQuotation->customer_email)) {
                $customer = Customer::withoutGlobalScope('store')->firstOrCreate(
                    ['email' => $customQuotation->customer_email],
                    [
                        'name' => $customQuotation->customer_name,
                        'phone' => $customQuotation->customer_phone,
                        'address' => $customQuotation->customer_address,
                        'customer_type' => 'walk_in',
                        'store_id' => session('admin_store_id') ?? auth()->user()?->store_id ?? 1,
                    ]
                );
            } elseif (! empty($customQuotation->customer_phone)) {
                $customer = Customer::withoutGlobalScope('store')->firstOrCreate(
                    ['phone' => $customQuotation->customer_phone],
                    [
                        'name' => $customQuotation->customer_name,
                        'email' => $customQuotation->customer_email,
                        'address' => $customQuotation->customer_address,
                        'customer_type' => 'walk_in',
                        'store_id' => session('admin_store_id') ?? auth()->user()?->store_id ?? 1,
                    ]
                );
            } else {
                $customer = Customer::create([
                    'name' => $customQuotation->customer_name,
                    'phone' => $customQuotation->customer_phone,
                    'email' => $customQuotation->customer_email,
                    'address' => $customQuotation->customer_address,
                    'customer_type' => 'walk_in',
                    'store_id' => session('admin_store_id') ?? auth()->user()?->store_id ?? 1,
                ]);
            }

            $project = Project::create([
                'ref_id' => ReferenceGenerator::generate('project'),
                'name' => $customQuotation->title,
                'description' => $customQuotation->description,
                'customer_id' => $customer->id,
                'customer_address' => $customQuotation->customer_address,
                'location' => $customQuotation->customer_address,
                'store_id' => session('admin_store_id') ?? auth()->user()?->store_id ?? 1,
                'contract_value' => $customQuotation->grand_total,
                'status' => Project::STATUS_QUOTATION,
                'start_date' => now()->toDateString(),
                'created_by' => auth()->id(),
                'material_cost' => 0,
                'labour_cost' => 0,
                'transport_cost' => 0,
                'other_cost' => 0,
                'project_cost' => 0,
                'amount_received' => 0,
                'balance' => $customQuotation->grand_total,
                'gross_profit' => $customQuotation->grand_total,
            ]);

            foreach ($customQuotation->items as $item) {
                $project->materials()->create([
                    'name' => $item->item,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_price,
                    'total' => $item->total,
                    'issued_to_inventory' => false,
                ]);
            }

            $customQuotation->update([
                'status' => 'converted',
                'project_id' => $project->id,
            ]);

            AuditLogger::log('converted', 'custom_quotation', $customQuotation->id, "Converted quotation {$customQuotation->ref_id} to project {$project->ref_id}");

            return $project;
        });

        return redirect()->route('admin.projects.show', $project)->with('success', "Quotation converted to project {$project->ref_id} successfully.");
    }

    public function destroy(CustomQuotation $customQuotation): RedirectResponse
    {
        $refId = $customQuotation->ref_id;
        $customQuotation->delete();

        AuditLogger::log('deleted', 'custom_quotation', $customQuotation->id, "Deleted custom quotation {$refId}");

        return redirect()->route('admin.custom-quotations.index')
            ->with('success', "Quotation {$refId} deleted.");
    }
}
