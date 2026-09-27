<?php

namespace App\Mail;

use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quotation $quotation
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Solar System Quotation - {$this->quotation->ref_id}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quotation',
            with: [
                'quotation' => $this->quotation,
            ],
        );
    }

    public function attachments(): array
    {
        // Generate PDF attachment
        $pdf = Pdf::loadView('emails.quotation-pdf', [
            'quotation' => $this->quotation,
        ])->output();

        return [
            Attachment::fromData(fn () => $pdf, "Quotation-{$this->quotation->ref_id}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
