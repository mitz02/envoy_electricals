<?php

namespace App\Mail;

use App\Models\Receipt;
use App\Models\Setting;
use App\Services\ReceiptPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Receipt $receipt) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Official Cash Receipt {$this->receipt->ref_id} - Envoy Electricals",
        );
    }

    public function content(): Content
    {
        $settings = Setting::whereIn('key', [
            'business.name',
            'business.phone',
            'business.email',
            'business.address',
        ])->pluck('value', 'key');

        return new Content(
            view: 'emails.receipt',
            with: [
                'receipt' => $this->receipt,
                'company' => [
                    'name' => $settings['business.name'] ?? 'Envoy Electricals',
                    'phone' => $settings['business.phone'] ?? '0809 708 9259, 0706 057 2282',
                    'email' => $settings['business.email'] ?? 'envoyelectricals@gmail.com',
                    'address' => $settings['business.address'] ?? 'Shop 1, Peace Avenue Junction, FUTA South Gate Road, Akure',
                ],
            ],
        );
    }

    public function attachments(): array
    {
        $pdfService = new ReceiptPdfService;
        $pdf = $pdfService->generate($this->receipt);

        return [
            Attachment::fromData(fn () => $pdf, "Receipt-{$this->receipt->ref_id}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
