<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentLinkMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Customer $customer,
        public Sale $sale,
        public string $paymentUrl,
        public ?string $customMessage = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment Link for Invoice {$this->sale->invoice_no} - {$this->sale->total}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-link',
            with: [
                'customer' => $this->customer,
                'sale' => $this->sale,
                'paymentUrl' => $this->paymentUrl,
                'customMessage' => $this->customMessage,
                'balance' => $this->sale->balance,
                'total' => $this->sale->total,
                'invoiceNo' => $this->sale->invoice_no,
                'refId' => $this->sale->ref_id,
            ],
        );
    }
}
