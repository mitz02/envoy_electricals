<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ReceiptMail;
use App\Models\Customer;
use App\Models\Receipt;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\NumberToWordsService;
use App\Services\ReceiptImageService;
use App\Services\ReceiptPdfService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReceiptController extends Controller
{
    public function __construct(
        protected ReceiptPdfService $pdfService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $search = $request->get('search');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $paymentMethod = $request->get('payment_method');

        $query = Receipt::query()
            ->with(['customer:id,name,phone,email', 'issuer:id,name'])
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('ref_id', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%")
                        ->orWhere('payment_for', 'like', "%{$search}%");
                });
            })
            ->when($dateFrom, fn ($q) => $q->whereDate('receipt_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('receipt_date', '<=', $dateTo))
            ->when($paymentMethod, fn ($q) => $q->where('payment_method', $paymentMethod))
            ->latest('receipt_date')
            ->latest('id');

        $receipts = $query->paginate(15)->withQueryString();

        $stats = [
            'total_count' => Receipt::count(),
            'total_amount' => (float) Receipt::sum('amount'),
            'this_month_amount' => (float) Receipt::whereMonth('receipt_date', Carbon::now()->month)
                ->whereYear('receipt_date', Carbon::now()->year)
                ->sum('amount'),
            'today_amount' => (float) Receipt::whereDate('receipt_date', Carbon::today())->sum('amount'),
        ];

        $defaultEnvoySig = Setting::where('key', 'receipt.envoy_signature')->value('value');
        $envoySignature = is_array($defaultEnvoySig) ? ($defaultEnvoySig['signature'] ?? '') : ($defaultEnvoySig ?? '');

        return Inertia::render('Admin/Receipts/Index', [
            'receipts' => $receipts,
            'filters' => [
                'search' => $search,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'payment_method' => $paymentMethod,
            ],
            'stats' => $stats,
            'defaultEnvoySignature' => $envoySignature,
        ]);
    }

    public function create(): InertiaResponse
    {
        $customers = Customer::query()
            ->select('id', 'name', 'phone', 'email')
            ->orderBy('name')
            ->get();

        $defaultEnvoySig = Setting::where('key', 'receipt.envoy_signature')->value('value');
        $envoySignature = is_array($defaultEnvoySig) ? ($defaultEnvoySig['signature'] ?? '') : ($defaultEnvoySig ?? '');

        return Inertia::render('Admin/Receipts/Create', [
            'customers' => $customers,
            'defaultEnvoySignature' => $envoySignature,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'receipt_date' => ['required', 'date'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'amount_in_words' => ['nullable', 'string'],
            'payment_for' => ['required', 'string'],
            'payment_method' => ['required', 'string', 'max:50'],
            'customer_signature' => ['nullable', 'string'],
            'envoy_signature' => ['nullable', 'string'],
            'signatory_title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        if (empty($data['amount_in_words'])) {
            $data['amount_in_words'] = NumberToWordsService::toNaira($data['amount']);
        }

        if (empty($data['envoy_signature'])) {
            $defaultEnvoySig = Setting::where('key', 'receipt.envoy_signature')->value('value');
            $data['envoy_signature'] = is_array($defaultEnvoySig) ? ($defaultEnvoySig['signature'] ?? null) : ($defaultEnvoySig ?? null);
        }

        $receipt = DB::transaction(function () use ($data) {
            return Receipt::create([
                'receipt_date' => $data['receipt_date'],
                'customer_id' => $data['customer_id'] ?? null,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'amount' => $data['amount'],
                'amount_in_words' => $data['amount_in_words'],
                'payment_for' => $data['payment_for'],
                'payment_method' => $data['payment_method'],
                'customer_signature' => $data['customer_signature'] ?? null,
                'envoy_signature' => $data['envoy_signature'] ?? null,
                'signed_at' => ! empty($data['customer_signature']) ? now() : null,
                'signatory_title' => $data['signatory_title'] ?? 'For Envoy Electricals',
                'issued_by' => Auth::id(),
                'store_id' => Auth::user()?->store_id,
                'status' => 'issued',
                'notes' => $data['notes'] ?? null,
            ]);
        });

        AuditLogger::log('created', 'receipt', $receipt->id, "Created cash receipt {$receipt->ref_id} for {$receipt->customer_name} (Amount: ₦{$receipt->amount})");

        // Send email immediately if requested and email provided
        if (! empty($data['send_email']) && ! empty($receipt->customer_email)) {
            try {
                Mail::to($receipt->customer_email)->send(new ReceiptMail($receipt));
                $receipt->update(['email_sent_at' => now(), 'status' => 'sent']);
            } catch (\Throwable $e) {
                Log::error('Failed to send receipt email: '.$e->getMessage());
            }
        }

        return redirect()->route('admin.receipts.index')
            ->with('success', "Receipt {$receipt->ref_id} created successfully.")
            ->with('created_receipt', [
                'id' => $receipt->id,
                'ref_id' => $receipt->ref_id,
                'customer_name' => $receipt->customer_name,
                'customer_phone' => $receipt->customer_phone,
                'public_url' => $receipt->public_url,
                'signing_url' => $receipt->signing_url,
                'has_signature' => ! empty($receipt->customer_signature),
                'whatsapp_text' => $this->buildWhatsAppMessage($receipt),
                'whatsapp_signing_text' => $this->buildWhatsAppSigningMessage($receipt, $receipt->signing_url),
            ]);
    }

    public function show(Receipt $receipt): InertiaResponse
    {
        $receipt->load(['customer', 'issuer', 'store']);

        return Inertia::render('Admin/Receipts/Show', [
            'receipt' => array_merge($receipt->toArray(), [
                'public_url' => $receipt->public_url,
                'signing_url' => $receipt->signing_url,
            ]),
            'whatsappMessage' => $this->buildWhatsAppMessage($receipt),
        ]);
    }

    public function edit(Receipt $receipt): InertiaResponse
    {
        $customers = Customer::query()
            ->select('id', 'name', 'phone', 'email')
            ->orderBy('name')
            ->get();

        $defaultEnvoySig = Setting::where('key', 'receipt.envoy_signature')->value('value');
        $envoySignature = is_array($defaultEnvoySig) ? ($defaultEnvoySig['signature'] ?? '') : ($defaultEnvoySig ?? '');

        return Inertia::render('Admin/Receipts/Edit', [
            'receipt' => $receipt,
            'customers' => $customers,
            'defaultEnvoySignature' => $envoySignature,
        ]);
    }

    public function update(Request $request, Receipt $receipt): RedirectResponse
    {
        $data = $request->validate([
            'receipt_date' => ['required', 'date'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'amount_in_words' => ['required', 'string'],
            'payment_for' => ['required', 'string'],
            'payment_method' => ['required', 'string', 'max:50'],
            'customer_signature' => ['nullable', 'string'],
            'envoy_signature' => ['nullable', 'string'],
            'signatory_title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! empty($data['customer_signature'])) {
            if (empty($receipt->signed_at)) {
                $data['signed_at'] = now();
            }
        } else {
            $data['signed_at'] = null;
        }

        $receipt->update($data);

        AuditLogger::log('updated', 'receipt', $receipt->id, "Updated cash receipt {$receipt->ref_id}");

        return redirect()->route('admin.receipts.index')
            ->with('success', "Receipt {$receipt->ref_id} updated successfully.");
    }

    public function saveDefaultEnvoySignature(Request $request): JsonResponse
    {
        $data = $request->validate([
            'signature' => ['nullable', 'string'],
        ]);

        $signature = $data['signature'] ?? null;

        Setting::updateOrCreate(
            ['key' => 'receipt.envoy_signature'],
            [
                'value' => ['signature' => $signature],
                'group' => 'receipt',
            ]
        );

        AuditLogger::log('updated', 'setting', null, 'Updated default Envoy Electricals signature for receipts');

        return response()->json([
            'success' => true,
            'message' => 'Default Envoy Electricals signature updated successfully.',
            'signature' => $signature,
        ]);
    }

    public function destroy(Receipt $receipt): RedirectResponse
    {
        $refId = $receipt->ref_id;
        $receipt->delete();

        AuditLogger::log('deleted', 'receipt', $receipt->id, "Deleted cash receipt {$refId}");

        return redirect()->route('admin.receipts.index')
            ->with('success', "Receipt {$refId} deleted successfully.");
    }

    public function downloadPdf(Receipt $receipt): Response
    {
        return $this->pdfService->download($receipt);
    }

    public function streamPdf(Receipt $receipt): Response
    {
        return $this->pdfService->stream($receipt);
    }

    public function downloadImage(Receipt $receipt): Response
    {
        $png = ReceiptImageService::generatePng($receipt);
        $filename = 'Receipt-'.$receipt->ref_id.'.png';

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function sendEmail(Request $request, Receipt $receipt): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:255'],
        ]);
        $email = $data['email'] ?? $receipt->customer_email;

        if (empty($email)) {
            return redirect()->back()->withErrors(['email' => 'Customer email address is required.']);
        }

        try {
            Mail::to($email)->send(new ReceiptMail($receipt));

            $receipt->update([
                'email_sent_at' => now(),
                'status' => 'sent',
                'customer_email' => $email,
            ]);

            AuditLogger::log('sent_email', 'receipt', $receipt->id, "E-Receipt {$receipt->ref_id} sent to {$email}");

            return redirect()->back()->with('success', "E-receipt sent successfully to {$email}.");
        } catch (\Throwable $e) {
            Log::error('Failed to send receipt email: '.$e->getMessage());

            return redirect()->back()->with('error', 'Failed to send email: '.$e->getMessage());
        }
    }

    public function recordWhatsApp(Receipt $receipt): RedirectResponse
    {
        $receipt->update([
            'whatsapp_sent_at' => now(),
            'status' => 'sent',
        ]);

        AuditLogger::log('sent_whatsapp', 'receipt', $receipt->id, "E-Receipt {$receipt->ref_id} shared via WhatsApp");

        return redirect()->back()->with('success', 'WhatsApp share recorded.');
    }

    /**
     * Return a WhatsApp URL containing the signing link so the admin can paste/send it.
     */
    public function sendSignatureLink(Request $request, Receipt $receipt): RedirectResponse
    {
        $signingUrl = $receipt->signing_url;

        AuditLogger::log('shared_signing_link', 'receipt', $receipt->id, "Signing link shared for {$receipt->ref_id}");

        return redirect()->back()->with('signing_link', [
            'url' => $signingUrl,
            'receipt' => $receipt->ref_id,
            'whatsapp_url' => $this->buildWhatsAppSigningMessage($receipt, $signingUrl),
        ]);
    }

    private function buildWhatsAppSigningMessage(Receipt $receipt, string $signingUrl): string
    {
        $amount = number_format((float) $receipt->amount, 2);

        return "Dear {$receipt->customer_name},\n\n"
            ."Please tap the link below to *review and sign* your official Cash Receipt from *Envoy Electricals*.\n\n"
            ."🧾 *Receipt No:* {$receipt->ref_id}\n"
            ."💰 *Amount:* ₦{$amount}\n"
            ."📋 *For:* {$receipt->payment_for}\n\n"
            ."✍️ *Sign your receipt here:*\n"
            ."{$signingUrl}\n\n"
            .'_Envoy Electricals — Powering your World with Innovative Solutions_';
    }

    /**
     * Build customer-friendly WhatsApp message.
     */
    public function buildWhatsAppMessage(Receipt $receipt): string
    {
        $amount = number_format((float) $receipt->amount, 2);
        $date = Carbon::parse($receipt->receipt_date)->format('d M, Y');
        $publicUrl = $receipt->public_url;

        return "Dear {$receipt->customer_name},\n\n"
            ."Thank you for your payment to *Envoy Electricals*.\n\n"
            ."🧾 *OFFICIAL CASH RECEIPT*\n"
            ."━━━━━━━━━━━━━━━━━━━━\n"
            ."• *Receipt No:* {$receipt->ref_id}\n"
            ."• *Date:* {$date}\n"
            ."• *Amount:* ₦{$amount}\n"
            ."• *In Words:* {$receipt->amount_in_words}\n"
            ."• *Payment For:* {$receipt->payment_for}\n"
            ."• *Payment Method:* {$receipt->payment_method}\n"
            ."━━━━━━━━━━━━━━━━━━━━\n\n"
            ."🔗 *View / Download Your Official E-Receipt Here:*\n"
            ."{$publicUrl}\n\n"
            ."*Envoy Electricals*\n"
            ."_Powering your World with Innovative Solutions_\n"
            ."📞 0809 708 9259, 0706 057 2282\n"
            .'Shop 1, Peace Avenue Junction, FUTA South Gate Road, Akure';
    }
}
