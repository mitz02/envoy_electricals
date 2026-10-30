<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Services\AuditLogger;
use App\Services\ReceiptImageService;
use App\Services\ReceiptPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PublicReceiptController extends Controller
{
    public function show(string $token): InertiaResponse
    {
        $receipt = Receipt::where('token', $token)->firstOrFail();

        return Inertia::render('Public/Receipt', [
            'receipt' => $receipt,
        ]);
    }

    public function signPage(string $token): InertiaResponse
    {
        $receipt = Receipt::where('token', $token)->firstOrFail();

        return Inertia::render('Public/SignReceipt', [
            'receipt' => $receipt,
        ]);
    }

    public function saveSignature(Request $request, string $token): RedirectResponse
    {
        $receipt = Receipt::where('token', $token)->firstOrFail();

        $data = $request->validate([
            'signature' => ['required', 'string'],
        ]);

        $receipt->update([
            'customer_signature' => $data['signature'],
            'signed_at' => now(),
        ]);

        AuditLogger::log('signed', 'receipt', $receipt->id, "Customer {$receipt->customer_name} electronically signed receipt {$receipt->ref_id}");

        return redirect()->route('receipt.show', $receipt->token)
            ->with('success', 'Thank you! Your signature has been recorded on the receipt.');
    }

    public function downloadPdf(string $token, ReceiptPdfService $pdfService): Response
    {
        $receipt = Receipt::where('token', $token)->firstOrFail();

        return $pdfService->download($receipt);
    }

    public function downloadImage(string $token): Response
    {
        $receipt = Receipt::where('token', $token)->firstOrFail();
        $png = ReceiptImageService::generatePng($receipt);
        $filename = 'Receipt-'.$receipt->ref_id.'.png';

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
