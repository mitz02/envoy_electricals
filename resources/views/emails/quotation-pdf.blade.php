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
        .price-breakdown { width: 100%; max-width: 450px; margin-left: auto; margin-top: 12px; }
        .price-line { display: table; width: 100%; padding: 12px 0; border-bottom: 1px solid #e8e4db; }
        .price-line.total { border-top: 2px solid #FACC15; border-bottom: none; margin-top: 8px; padding-top: 18px; }
        .price-label { display: table-cell; color: #4a5568; font-size: 12px; font-weight: 500; }
        .price-label.total { color: #0D1527; font-size: 14px; font-weight: 800; }
        .price-value { display: table-cell; text-align: right; font-weight: 700; color: #0D1527; font-size: 13px; font-variant-numeric: tabular-nums; }
        .price-value.total { color: #FACC15; font-size: 18px; }
        .price-value.logistics { color: #40e0d0; }
        .notes { background: #fffef7; border: 1px solid #FACC15; border-radius: 6px; padding: 15px; margin: 20px 0; font-size: 10px; color: #785400; }
        .validity { background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 12px; margin: 20px 0; font-size: 10px; color: #991b1b; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 2px solid #0D1527; text-align: center; color: #6b7280; font-size: 9px; }
        .footer-brand { color: #0D1527; font-size: 14px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 2px; }
        .footer-tagline { color: #40e0d0; font-size: 10px; font-weight: 600; margin-bottom: 10px; }
        .footer-contact a { color: #0D1527; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <?php
    $settings = \App\Models\Setting::all(['key', 'value'])->pluck('value', 'key');
    $companyPhone = $settings['business.phone'] ?? '+234 809 708 9259';
    $companyEmail = $settings['business.email'] ?? 'hello@envoyelectric.com';
    $companyWebsite = parse_url(env('APP_URL', 'https://envoyelectricals.com'), PHP_URL_HOST);
    ?>
    <div class="header">
        <div class="logo">ENVOY ELECTRICALS</div>
        <div class="tagline">Powering Your World with Clean Energy</div>
    </div>

    <h1 class="quotation-title">SOLAR SYSTEM QUOTATION</h1>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell info-label">Quotation No:</div>
            <div class="info-cell info-value">{{ $quotation->ref_id }}</div>
        </div>
        <div class="info-row">
            <div class="info-cell info-label">Date:</div>
            <div class="info-cell info-value">{{ \Carbon\Carbon::parse($quotation->created_at)->format('F d, Y') }}</div>
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
            <div class="info-cell info-label">Location:</div>
            <div class="info-cell info-value">{{ $quotation->location ?: 'Not specified' }}</div>
        </div>
    </div>

    <div class="section-title">RECOMMENDED SYSTEM</div>
    <p><strong>{{ $quotation->recommended_system }}</strong></p>

    <div class="section-title">PRICE BREAKDOWN</div>
    <div class="price-breakdown">
        <div class="price-line">
            <span class="price-label">Solar System (Panels, Inverter, Batteries, Installation)</span>
            <span class="price-value">₦{{ number_format($quotation->estimated_price, 0) }}</span>
        </div>
        @if($quotation->additional_logistics && $quotation->additional_logistics > 0)
        <div class="price-line">
            <span class="price-label">Additional Logistics & Delivery</span>
            <span class="price-value logistics">₦{{ number_format($quotation->additional_logistics, 0) }}</span>
        </div>
        @endif
        <div class="price-line total">
            <span class="price-label total">Total Investment</span>
            <span class="price-value total">₦{{ number_format(($quotation->estimated_price ?? 0) + ($quotation->additional_logistics ?? 0), 0) }}</span>
        </div>
    </div>

    @if($quotation->notes)
    <div class="notes">
        <strong>Terms & Notes:</strong><br>
        {{ nl2br($quotation->notes) }}
    </div>
    @endif

    <div class="validity">
        <strong>Validity:</strong> This quotation is valid for 30 days from the date of issue. Prices are subject to change based on market conditions and site survey requirements. A site survey is required before final installation to confirm exact specifications and any additional costs.
    </div>

    <div class="footer">
        <div class="footer-brand">Envoy Electricals</div>
        <div class="footer-tagline">Powering Your World with Clean Energy</div>
        <p>📞 {{ $companyPhone }} &nbsp;|&nbsp; ✉️ <a href="mailto:{{ $companyEmail }}">{{ $companyEmail }}</a> &nbsp;|&nbsp; 🌐 <a href="https://{{ $companyWebsite }}">{{ $companyWebsite }}</a></p>
        <p style="margin-top: 10px; font-size: 8px;">This is a computer-generated document. No signature required.</p>
    </div>
</body>
</html>