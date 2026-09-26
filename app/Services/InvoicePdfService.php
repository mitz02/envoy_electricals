<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Generates PDF invoices for sales.
 */
class InvoicePdfService
{
    /**
     * Generate PDF for a sale and return the binary content.
     */
    public function generate(Sale $sale): string
    {
        $sale->load(['items.product', 'customer', 'salesperson', 'store']);

        $settings = $this->getSettings();

        $pdf = Pdf::loadView('pdf.invoice', [
            'sale' => $sale,
            'settings' => $settings,
            'items' => $sale->items,
            'company' => [
                'name' => $settings['business.name'] ?? 'Envoy Electricals',
                'address' => $settings['business.address'] ?? '',
                'email' => $settings['business.email'] ?? 'hello@envoyelectric.com',
                'phone' => $settings['business.phone'] ?? '+234 809 708 9259',
            ],
        ])->setPaper('A4', 'portrait');

        return $pdf->output();
    }

    /**
     * Generate and download the PDF.
     */
    public function download(Sale $sale): BinaryFileResponse
    {
        $sale->load(['items.product', 'customer', 'salesperson', 'store']);

        $settings = $this->getSettings();

        $pdf = Pdf::loadView('pdf.invoice', [
            'sale' => $sale,
            'settings' => $settings,
            'items' => $sale->items,
            'company' => [
                'name' => $settings['business.name'] ?? 'Envoy Electricals',
                'address' => $settings['business.address'] ?? '',
                'email' => $settings['business.email'] ?? 'hello@envoyelectric.com',
                'phone' => $settings['business.phone'] ?? '+234 809 708 9259',
            ],
        ])->setPaper('A4', 'portrait');

        $filename = 'Invoice-'.$sale->invoice_no.'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Get business settings.
     */
    protected function getSettings(): array
    {
        return Setting::whereIn('key', [
            'business.name',
            'business.address',
            'business.email',
            'business.phone',
            'currency.symbol',
            'tax.rate',
        ])->pluck('value', 'key')->toArray();
    }
}
