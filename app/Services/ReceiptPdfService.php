<?php

namespace App\Services;

use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ReceiptPdfService
{
    /**
     * Generate binary PDF string for the receipt.
     */
    public function generate(Receipt $receipt): string
    {
        $pdf = Pdf::loadView('pdf.receipt', [
            'receipt' => $receipt,
        ])->setPaper([0, 0, 1012, 428], 'landscape');

        return $pdf->output();
    }

    /**
     * Return PDF download response.
     */
    public function download(Receipt $receipt): Response
    {
        $pdf = Pdf::loadView('pdf.receipt', [
            'receipt' => $receipt,
        ])->setPaper([0, 0, 1012, 428], 'landscape');

        $filename = 'Receipt-'.$receipt->ref_id.'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Return PDF stream (inline preview) response.
     */
    public function stream(Receipt $receipt): Response
    {
        $pdf = Pdf::loadView('pdf.receipt', [
            'receipt' => $receipt,
        ])->setPaper([0, 0, 1012, 428], 'landscape');

        $filename = 'Receipt-'.$receipt->ref_id.'.pdf';

        return $pdf->stream($filename);
    }
}
