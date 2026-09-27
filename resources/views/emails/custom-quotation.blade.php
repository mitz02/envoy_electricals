<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation - {{ $quotation->ref_id }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #1f2937; margin: 0; padding: 0; background-color: #FAF8F2; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(13,21,39,0.08); overflow: hidden; border: 1px solid #e8e4db; }
        .header { background: #0D1527; color: white; padding: 36px 24px; text-align: center; position: relative; overflow: hidden; }
        .header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #FACC15, #40e0d0); }
        .header h1 { margin: 0; font-size: 28px; font-weight: 800; letter-spacing: -0.5px; }
        .header p { margin: 10px 0 0; opacity: 0.85; font-size: 15px; font-weight: 400; }
        .content { padding: 36px 28px; }
        .greeting { font-size: 17px; margin-bottom: 24px; color: #0D1527; font-weight: 500; }
        .intro { color: #4a5568; margin-bottom: 28px; font-size: 15px; }
        .quotation-details { background: #FAF8F2; border: 1px solid #e8e4db; border-radius: 12px; padding: 20px; margin: 24px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e8e4db; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #6b7280; font-size: 13px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-value { font-weight: 600; color: #0D1527; font-size: 14px; }
        .price-breakdown { background: white; border: 2px solid #0D1527; border-radius: 12px; padding: 24px; margin: 28px 0; }
        .price-breakdown-title { font-size: 12px; font-weight: 700; color: #0D1527; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; text-align: center; }
        .price-line { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #e8e4db; }
        .price-line:last-of-type { border-bottom: none; margin-top: 8px; padding-top: 20px; }
        .price-line.total { border-top: 2px solid #FACC15; border-bottom: none; margin-top: 16px; padding-top: 20px; }
        .price-label { color: #4a5568; font-size: 15px; font-weight: 500; }
        .price-label.total { color: #0D1527; font-size: 17px; font-weight: 700; }
        .price-value { font-weight: 700; color: #0D1527; font-size: 16px; font-variant-numeric: tabular-nums; }
        .price-value.total { color: #FACC15; font-size: 24px; }
        .footer { background: #0D1527; padding: 28px 24px; text-align: center; border-top: 1px solid #1a2a4a; }
        .footer-brand { color: #FACC15; font-size: 18px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 4px; }
        .footer-tagline { color: #40e0d0; font-size: 13px; font-weight: 500; margin-bottom: 16px; }
        .footer-contact { color: rgba(255,255,255,0.7); font-size: 12px; line-height: 1.8; }
        .footer-contact a { color: #FACC15; text-decoration: none; font-weight: 600; }
        .note { background: #fffef7; border: 1px solid #FACC15; border-radius: 10px; padding: 16px; margin: 24px 0; font-size: 13px; color: #785400; }
        .validity { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 14px; margin: 24px 0; font-size: 12px; color: #991b1b; }
        .btn { display: inline-block; background: linear-gradient(135deg, #0D1527, #1a2a4a); color: white; padding: 14px 28px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; margin-top: 16px; box-shadow: 0 4px 14px rgba(13,21,39,0.25); }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>Envoy Electricals</h1>
                <p>Your Quotation</p>
            </div>
            <div class="content">
                <p class="greeting">Dear {{ $quotation->customer_name }},</p>
                <p class="intro">Thank you for your interest in Envoy Electricals. We have prepared a quotation for <strong>{{ $quotation->title }}</strong> ({{ $quotation->job_type }}).</p>

                <div class="quotation-details">
                    <div class="detail-row">
                        <span class="detail-label">Quotation Reference</span>
                        <span class="detail-value">{{ $quotation->ref_id }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date</span>
                        <span class="detail-value">{{ \Carbon\Carbon::parse($quotation->quotation_date)->format('F d, Y') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Valid Until</span>
                        <span class="detail-value">{{ $quotation->valid_until ? \Carbon\Carbon::parse($quotation->valid_until)->format('F d, Y') : '30 days from date' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Job Type</span>
                        <span class="detail-value">{{ $quotation->job_type }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Items</span>
                        <span class="detail-value">{{ count($quotation->items) }} item(s)</span>
                    </div>
                </div>

                <div class="price-breakdown">
                    <div class="price-breakdown-title">Price Breakdown</div>
                    <div class="price-line">
                        <span class="price-label">Subtotal</span>
                        <span class="price-value">₦{{ number_format($subtotal, 0) }}</span>
                    </div>
                    @if($quotation->discount > 0)
                    <div class="price-line">
                        <span class="price-label">Discount</span>
                        <span class="price-value" style="color: #ef4444;">- ₦{{ number_format($quotation->discount, 0) }}</span>
                    </div>
                    @endif
                    @if($quotation->tax > 0)
                    <div class="price-line">
                        <span class="price-label">Tax / VAT</span>
                        <span class="price-value">+ ₦{{ number_format($quotation->tax, 0) }}</span>
                    </div>
                    @endif
                    @if($quotation->other_charges > 0)
                    <div class="price-line">
                        <span class="price-label">Other Charges</span>
                        <span class="price-value">+ ₦{{ number_format($quotation->other_charges, 0) }}</span>
                    </div>
                    @endif
                    <div class="price-line total">
                        <span class="price-label total">Grand Total</span>
                        <span class="price-value total">₦{{ number_format($quotation->grand_total, 0) }}</span>
                    </div>
                </div>

                @if($quotation->notes)
                <div class="note">
                    <strong>Notes:</strong> {{ nl2br($quotation->notes) }}
                </div>
                @endif

                <div class="validity">
                    <strong>Validity:</strong> This quotation is valid until {{ $quotation->valid_until ? \Carbon\Carbon::parse($quotation->valid_until)->format('F d, Y') : '30 days from date of issue' }}. Prices are subject to change based on market conditions and site survey requirements.
                </div>

                <p>Our team will contact you to discuss this quotation and answer any questions you may have.</p>

                <a href="{{ url('/packages') }}" class="btn">View Our Services</a>
            </div>
            <div class="footer">
                <div class="footer-brand">Envoy Electricals</div>
                <div class="footer-tagline">Powering Your World with Clean Energy</div>
                <div class="footer-contact">
                    <p>📞 +234-XXX-XXXXXX &nbsp;|&nbsp; ✉️ <a href="mailto:info@envoyelectricals.com">info@envoyelectricals.com</a> &nbsp;|&nbsp; 🌐 <a href="https://envoyelectricals.com">envoyelectricals.com</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>