<?php

namespace App\Mail;

use App\Models\CustomQuotation;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomQuotationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CustomQuotation $quotation
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Quotation {$this->quotation->ref_id} - {$this->quotation->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.custom-quotation',
            with: [
                'quotation' => $this->quotation,
            ],
        );
    }

    public function attachments(): array
    {
        $settings = $this->getSettings();

        $pdf = Pdf::loadView('pdf.quote-invoice', [
            'quotation' => $this->quotation,
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
        ])->output();

        return [
            Attachment::fromData(fn () => $pdf, "Quotation-{$this->quotation->ref_id}.pdf")
                ->withMime('application/pdf'),
        ];
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
}
