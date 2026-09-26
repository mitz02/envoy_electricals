@extends('emails.layouts.new')

@section('preheader')
    Payment link for Invoice {{ $invoiceNo }} — {{ \App\Helpers\Currency::format($balance) }} due
@endsection

@section('eyebrow')
    Invoice Payment
@endsection

@section('title')
    Invoice {{ $invoiceNo }} — {{ \App\Helpers\Currency::format($total) }}
@endsection

@section('subtitle')
    Hello {{ $customer->name }}, you have an outstanding balance of <strong>{{ \App\Helpers\Currency::format($balance) }}</strong> on this invoice.
@endsection

@section('content')
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#475569;">
        Hi {{ $customer->name }},
    </p>

    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#475569;">
        Thank you for your purchase from Envoy Electricals. Invoice <strong>{{ $invoiceNo }}</strong> (Ref: {{ $refId }}) has an outstanding balance of <strong style="font-size:18px;color:#0D1527;">{{ \App\Helpers\Currency::format($balance) }}</strong>.
    </p>

    @if ($customMessage)
    <div style="margin:0 0 24px;padding:16px;background:#FEF3C7;border:1px solid #FCD34D;border-radius:10px;">
        <p style="margin:0;font-size:14px;line-height:1.6;color:#92400E;">{{ $customMessage }}</p>
    </div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding:12px 0 4px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size:13px;color:#64748B;padding:6px 0;">Invoice Number</td>
                        <td style="font-size:13px;color:#0D1527;font-weight:700;padding:6px 0;text-align:right;">{{ $invoiceNo }}</td>
                    </tr>
                    <tr>
                        <td style="font-size:13px;color:#64748B;padding:6px 0;">Reference</td>
                        <td style="font-size:13px;color:#0D1527;font-weight:700;padding:6px 0;text-align:right;">{{ $refId }}</td>
                    </tr>
                    <tr>
                        <td style="font-size:13px;color:#64748B;padding:6px 0;">Date</td>
                        <td style="font-size:13px;color:#0D1527;font-weight:700;padding:6px 0;text-align:right;">{{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td style="font-size:13px;color:#64748B;padding:6px 0;">Total Amount</td>
                        <td style="font-size:13px;color:#0D1527;font-weight:700;padding:6px 0;text-align:right;">{{ \App\Helpers\Currency::format($total) }}</td>
                    </tr>
                    <tr style="border-top:2px solid #ECE6DA;">
                        <td style="font-size:14px;color:#DC2626;font-weight:800;padding:10px 0 0;">Balance Due</td>
                        <td style="font-size:14px;color:#DC2626;font-weight:800;padding:10px 0 0;text-align:right;">{{ \App\Helpers\Currency::format($balance) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin:24px 0 0;font-size:15px;line-height:1.6;color:#475569;">
        Click the button below to pay securely via Paystack (cards, bank transfer, USSD):
    </p>
@endsection

@section('action')
    {{ $paymentUrl }}
@endsection

@section('actionLabel')
    Pay {{ \App\Helpers\Currency::format($balance) }} Now
@endsection