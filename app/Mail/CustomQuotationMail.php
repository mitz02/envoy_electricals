<?php

namespace App\Mail;

use App\Models\CustomQuotation;
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
        $pdf = Pdf::loadView('pdf.custom-quotation', [
            'quotation' => $this->quotation,
        ])->output();

        return [
            Attachment::fromData(fn () => $pdf, "Quotation-{$this->quotation->ref_id}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
