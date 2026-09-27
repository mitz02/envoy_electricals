<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation - {{ $quotation->ref_id }}</title>
    <style>
        @page { margin: 20mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.5; color: #1f2937; }
        .header { text-align: center; border-bottom: 3px solid #0D1527; padding-bottom: 20px; margin-bottom: 30px; }
        .header::before { content: ''; display: block; width: 60px; height: 4px; background: linear-gradient(90deg, #FACC15, #40e0d0); margin: 0 auto 16px; border-radius: 2px; }
        .logo { font-size: 28px; font-weight: bold; color: #0D1527; margin-bottom: 5px; letter-spacing: -0.5px; }
        .tagline { color: #40e0d0; font-size: 14px; font-weight: 500; }
        .quotation-title { font-size: 22px; font-weight: bold; color: #0D1527; margin: 20px 0; text-align: center; letter-spacing: -0.5px; }
        .info-grid { display: table; width: 100%; margin-bottom: 20px; }
        .info-row { display: table-row; }
        .info-cell { display: table-cell; padding: 10px 14px; }
        .info-label { font-weight: 700; color: #0D1527; width: 30%; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { color: #1f2937; font-size: 11px; }
        .section-title { font-size: 13px; font-weight: 700; color: #0D1527; border-bottom: 2px solid #0D1527; padding-bottom: 6px; margin: 24px 0 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        .items-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .items-table th, .items-table td { border: 1px solid #d1d5db; padding: 10px; text-align: left; }
        .items-table th { background: #0D1527; color: white; font-weight: 600; }
        .items-table td.number { text-align: right; }
        .items-table td.center { text-align: center; }
        .items-table tr:nth-child(even) td { background: #f9fafb; }
        .price-summary { width: 100%; max-width: 400px; margin-left: auto; margin-top: 20px; }
        .price-row { display: table; width: 100%; padding: 8px 0; }
        .price-row.total { border-top: 2px solid #FACC15; margin-top: 10px; padding-top: 15px; font-weight: bold; font-size: 14px; }
        .price-label { display: table-cell; color: #4a5568; font-size: 12px; font-weight: 500; }
        .price-label.total { color: #0D1527; font-size: 14px; font-weight: 800; }
        .price-value { display: table-cell; text-align: right; font-weight: 700; color: #0D1527; font-size: 13px; font-variant-numeric: tabular-nums; }
        .price-value.total { color: #FACC15; font-size: 18px; }
        .price-value.discount { color: #ef4444; }
        .notes { background: #fffef7; border: 1px solid #FACC15; border-radius: 6px; padding: 15px; margin: 20px 0; font-size: 10px; color: #785400; }
        .description { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; margin: 20px 0; font-size: 11px; color: #374151; }
        .validity { background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 12px; margin: 20px 0; font-size: 10px; color: #991b1b; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 2px solid #0D1527; text-align: center; color: #6b7280; font-size: 9px; }
        .footer-brand { color: #0D1527; font-size: 14px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 2px; }
        .footer-tagline { color: #40e0d0; font-size: 10px; font-weight: 600; margin-bottom: 10px; }
        .footer-contact a { color: #0D1527; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">ENVOY ELECTRICALS</div>
        <div class="tagline">Powering Your World with Clean Energy</div>
    </div>

    <h1 class="quotation-title">QUOTATION</h1>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell info-label">Quotation No:</div>
            <div class="info-cell info-value">{{ $quotation->ref_id }}</div>
        </div>
        <div class="info-row">
            <div class="info-cell info-label">Date:</div>
            <div class="info-cell info-value">{{ \Carbon\Carbon::parse($quotation->quotation_date)->format('F d, Y') }}</div>
        </div>
        <div class="info-row">
            <div class="info-cell info-label">Valid Until:</div>
            <div class="info-cell info-value">{{ $quotation->valid_until ? \Carbon\Carbon::parse($quotation->valid_until)->format('F d, Y') : 'Not specified' }}</div>
        </div>
        <div class="info-row">
            <div class="info-cell info-label">Job Type:</div>
            <div class="info-cell info-value">{{ $quotation->job_type }}</div>
        </div>
        <div class="info-row">
            <div class="info-cell info-label">Customer:</div>
            <div class="info-cell info-value">{{ $quotation->customer_name }}</div>
        </div>
        <div class="info-row">
            <div class="info-cell info-label">Phone:</div>
            <div class="info-cell info-value">{{ $quotation->customer_phone }}</div>
        </div>
        <div class="info-row">
            <div class="info-cell info-label">Email:</div>
            <div class="info-cell info-value">{{ $quotation->customer_email ?: 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-cell info-label">Company:</div>
            <div class="info-cell info-value">{{ $quotation->customer_company ?: 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-cell info-label">Address:</div>
            <div class="info-cell info-value">{{ $quotation->customer_address ?: 'Not specified' }}</div>
        </div>
    </div>

    <div class="section-title">QUOTATION TITLE</div>
    <p><strong>{{ $quotation->title }}</strong></p>

    @if($quotation->description)
    <div class="section-title">DESCRIPTION</div>
    <div class="description">{{ nl2br($quotation->description) }}</div>
    @endif

    <div class="section-title">ITEMS</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 30%;">Item</th>
                <th style="width: 30%;">Description</th>
                <th class="center" style="width: 8%;">Qty</th>
                <th class="center" style="width: 8%;">Unit</th>
                <th class="number" style="width: 12%;">Unit Price</th>
                <th class="number" style="width: 12%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $item)
            <tr>
                <td>{{ $item->item }}</td>
                <td>{{ $item->description ?: '—' }}</td>
                <td class="center">{{ $item->quantity }}</td>
                <td class="center">{{ $item->unit ?: 'pcs' }}</td>
                <td class="number">₦{{ number_format($item->unit_price, 0) }}</td>
                <td class="number">₦{{ number_format($item->total, 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="price-summary">
        <div class="price-row">
            <span class="price-label">Subtotal</span>
            <span class="price-value">₦{{ number_format($subtotal, 0) }}</span>
        </div>
        @if($quotation->discount > 0)
        <div class="price-row">
            <span class="price-label">Discount</span>
            <span class="price-value discount">- ₦{{ number_format($quotation->discount, 0) }}</span>
        </div>
        @endif
        @if($quotation->tax > 0)
        <div class="price-row">
            <span class="price-label">Tax / VAT</span>
            <span class="price-value">+ ₦{{ number_format($quotation->tax, 0) }}</span>
        </div>
        @endif
        @if($quotation->other_charges > 0)
        <div class="price-row">
            <span class="price-label">Other Charges</span>
            <span class="price-value">+ ₦{{ number_format($quotation->other_charges, 0) }}</span>
        </div>
        @endif
        <div class="price-row total">
            <span class="price-label total">Grand Total</span>
            <span class="price-value total">₦{{ number_format($quotation->grand_total, 0) }}</span>
        </div>
    </div>

    @if($quotation->notes)
    <div class="notes">
        <strong>Terms & Notes:</strong><br>
        {{ nl2br($quotation->notes) }}
    </div>
    @endif

    <div class="validity">
        <strong>Validity:</strong> This quotation is valid until {{ $quotation->valid_until ? \Carbon\Carbon::parse($quotation->valid_until)->format('F d, Y') : '30 days from date of issue' }}. Prices are subject to change based on market conditions and site survey requirements.
    </div>

    <div class="footer">
        <div class="footer-brand">Envoy Electricals</div>
        <div class="footer-tagline">Powering Your World with Clean Energy</div>
        <p>📞 +234-XXX-XXXXXX &nbsp;|&nbsp; ✉️ <a href="mailto:info@envoyelectricals.com">info@envoyelectricals.com</a> &nbsp;|&nbsp; 🌐 <a href="https://envoyelectricals.com">envoyelectricals.com</a></p>
        <p style="margin-top: 10px; font-size: 8px;">This is a computer-generated document. No signature required.</p>
    </div>
</body>
</html>