<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation - {{ $quotation->ref_id }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #1f2937; margin: 0; padding: 0; background-color: #f8fafc; }
        .container { max-width: 640px; margin: 0 auto; padding: 24px; }
        .card { background: white; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden; border: 1px solid #e5e7eb; }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            background: white;
            padding: 28px 24px;
            border-bottom: 2px solid #1e3a5f;
        }
        .company-info { flex: 1; }
        .company-logo {
            font-size: 22px;
            font-weight: 800;
            color: #0D1527;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }
        .company-tagline {
            color: #1e3a5f;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 10px;
        }
        .company-details {
            font-size: 11px;
            color: #6b7280;
            line-height: 1.6;
        }
        .company-details div { margin: 2px 0; }
        .invoice-header { text-align: right; flex: 1; }
        .invoice-title {
            font-size: 24px;
            font-weight: 800;
            color: #1e3a5f;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }
        .invoice-meta {
            font-size: 11px;
            color: #6b7280;
            text-align: right;
            line-height: 1.8;
        }
        .invoice-meta div { margin: 3px 0; }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 6px;
        }
        .status-draft { background: #fef3c7; color: #92400e; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-viewed { background: #dbeafe; color: #1e40af; }
        .status-accepted { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-expired { background: #fef3c7; color: #92400e; }
        .status-converted { background: #d1fae5; color: #065f46; }
        .content { padding: 28px 24px; }
        .greeting { font-size: 16px; margin-bottom: 20px; color: #0D1527; font-weight: 500; }
        .intro { color: #4a5568; margin-bottom: 24px; font-size: 14px; }
        .details-section {
            display: flex;
            gap: 24px;
            margin-bottom: 28px;
        }
        .detail-block { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; }
        .detail-label {
            font-size: 9px;
            font-weight: 700;
            color: #1e3a5f;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row { margin: 6px 0; font-size: 12px; }
        .detail-row-label { color: #6b7280; font-weight: 500; }
        .detail-row-value { color: #1f2937; font-weight: 500; margin-left: 8px; }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
            font-size: 12px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #e5e7eb;
            padding: 10px 8px;
            text-align: left;
        }
        .items-table th {
            background: #1e3a5f;
            color: white;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .items-table td { color: #374151; }
        .items-table td.number { text-align: right; font-variant-numeric: tabular-nums; }
        .items-table td.center { text-align: center; }
        .items-table tr:nth-child(even) td { background: #f9fafb; }
        .financial-summary {
            width: 100%;
            max-width: 320px;
            margin-left: auto;
            margin-bottom: 28px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 12px;
        }
        .summary-row:last-child { border-bottom: none; }
        .summary-label { color: #6b7280; font-weight: 500; }
        .summary-value { color: #1f2937; font-weight: 600; font-variant-numeric: tabular-nums; }
        .summary-row.total {
            border-top: 2px solid #1e3a5f;
            border-bottom: none;
            margin-top: 8px;
            padding-top: 14px;
            font-size: 14px;
            font-weight: 800;
        }
        .summary-row.total .summary-label { color: #1e3a5f; }
        .summary-row.total .summary-value { color: #1e3a5f; }
        .summary-row.due .summary-value { color: #dc2626; font-weight: 700; }
        .payment-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .payment-title {
            font-size: 10px;
            font-weight: 700;
            color: #1e3a5f;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .payment-grid {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        .payment-item { flex: 1; min-width: 150px; }
        .payment-item-label {
            font-size: 9px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .payment-item-value {
            font-size: 12px;
            color: #1f2937;
            font-weight: 500;
        }
        .notes-section {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .notes-title {
            font-size: 9px;
            font-weight: 700;
            color: #92400e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .notes-content {
            font-size: 12px;
            color: #78350f;
            line-height: 1.6;
            white-space: pre-line;
        }
        .footer {
            background: #1e3a5f;
            padding: 24px;
            text-align: center;
            color: rgba(255,255,255,0.8);
            font-size: 11px;
            line-height: 1.8;
        }
        .footer-tagline {
            color: #FACC15;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .btn { display: inline-block; background: #1e3a5f; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px; margin-top: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <!-- HEADER -->
            <div class="header">
                <div class="company-info">
                    <div class="company-logo">{{ $businessName ?? 'Envoy Electricals' }}</div>
                    <div class="company-tagline">Powering your world with innovative solutions</div>
                    <div class="company-details">
                        <div>{{ $businessEmail ?? 'envoyelectricals@gmail.com' }}</div>
                        <div>{{ $businessAddress ?? 'Shop 1, Peace Avenue Junction, Futa Southgate Rd, Akure' }}</div>
                        <div>Tel: {{ $businessPhone ?? '+234 809 708 9259' }}</div>
                    </div>
                </div>
                <div class="invoice-header">
                    <div class="invoice-title">QUOTATION</div>
                    <div class="invoice-meta">
                        <div><strong>Quotation No:</strong> {{ $quotation->ref_id }}</div>
                        <div><strong>Status:</strong> 
                            <span class="status-badge status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <p class="greeting">Dear {{ $quotation->customer_name }},</p>
                <p class="intro">Thank you for your interest in our services. We have prepared a quotation for <strong>{{ $quotation->title }}</strong> ({{ $quotation->job_type }}).</p>

                <!-- BILL TO & DETAILS -->
                <div class="details-section">
                    <div class="detail-block">
                        <div class="detail-label">Bill To</div>
                        <div class="detail-row">
                            <span class="detail-row-label">Customer:</span>
                            <span class="detail-row-value">{{ $quotation->customer_name }}</span>
                        </div>
                        @if($quotation->customer_company)
                        <div class="detail-row">
                            <span class="detail-row-label">Company:</span>
                            <span class="detail-row-value">{{ $quotation->customer_company }}</span>
                        </div>
                        @endif
                        <div class="detail-row">
                            <span class="detail-row-label">Location:</span>
                            <span class="detail-row-value">{{ $quotation->customer_address ?: 'Not specified' }}</span>
                        </div>
                    </div>

                    <div class="detail-block">
                        <div class="detail-label">Details</div>
                        <div class="detail-row">
                            <span class="detail-row-label">Issue Date:</span>
                            <span class="detail-row-value">{{ \Carbon\Carbon::parse($quotation->quotation_date)->format('m/d/Y') }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-row-label">Valid Until:</span>
                            <span class="detail-row-value">{{ $quotation->valid_until ? \Carbon\Carbon::parse($quotation->valid_until)->format('m/d/Y') : 'Not specified' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-row-label">Job Type:</span>
                            <span class="detail-row-value">{{ $quotation->job_type }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-row-label">Reference:</span>
                            <span class="detail-row-value">{{ $quotation->title }}</span>
                        </div>
                    </div>
                </div>

                <!-- ITEMS TABLE -->
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Item</th>
                            <th style="width: 12%; text-align: center;">Qty</th>
                            <th style="width: 18%; text-align: right;">Price</th>
                            <th style="width: 15%; text-align: center;">Discount / Bonus</th>
                            <th style="width: 20%; text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quotation->items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->item }}</strong>
                                @if($item->description)
                                <div style="font-size: 10px; color: #6b7280; margin-top: 3px;">{{ $item->description }}</div>
                                @endif
                            </td>
                            <td class="center">{{ $item->quantity }} {{ $item->unit ?: 'pcs' }}</td>
                            <td class="number">₦{{ number_format($item->unit_price, 0) }}</td>
                            <td class="center">—</td>
                            <td class="number"><strong>₦{{ number_format($item->total, 0) }}</strong></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- FINANCIAL SUMMARY -->
                <?php
                    $subtotal = $quotation->items->sum('total');
                    $discount = $quotation->discount ?? 0;
                    $tax = $quotation->tax ?? 0;
                    $otherCharges = $quotation->other_charges ?? 0;
                    $grandTotal = $subtotal - $discount + $tax + $otherCharges;
                    $amountPaid = 0;
                    $amountDue = $grandTotal - $amountPaid;
                ?>
                <div class="financial-summary">
                    <div class="summary-row">
                        <span class="summary-label">Subtotal</span>
                        <span class="summary-value">₦{{ number_format($subtotal, 0) }}</span>
                    </div>
                    @if($discount > 0)
                    <div class="summary-row">
                        <span class="summary-label">Discount</span>
                        <span class="summary-value" style="color: #dc2626;">- ₦{{ number_format($discount, 0) }}</span>
                    </div>
                    @endif
                    @if($tax > 0)
                    <div class="summary-row">
                        <span class="summary-label">Tax / VAT</span>
                        <span class="summary-value">+ ₦{{ number_format($tax, 0) }}</span>
                    </div>
                    @endif
                    @if($otherCharges > 0)
                    <div class="summary-row">
                        <span class="summary-label">Other Charges</span>
                        <span class="summary-value">+ ₦{{ number_format($otherCharges, 0) }}</span>
                    </div>
                    @endif
                    <div class="summary-row total">
                        <span class="summary-label">Total</span>
                        <span class="summary-value">₦{{ number_format($grandTotal, 0) }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Amount Paid</span>
                        <span class="summary-value">₦{{ number_format($amountPaid, 0) }}</span>
                    </div>
                    <div class="summary-row due">
                        <span class="summary-label">Amount Due</span>
                        <span class="summary-value">₦{{ number_format($amountDue, 0) }}</span>
                    </div>
                </div>

                <!-- PAYMENT DETAILS -->
                <?php
                    $bankName = \App\Models\Setting::where('key', 'bank.bank_name')->value('value') ?? 'Wema Bank';
                    $bankAccountName = \App\Models\Setting::where('key', 'bank.account_name')->value('value') ?? 'Envoy Electricals';
                    $bankAccountNumber = \App\Models\Setting::where('key', 'bank.account_number')->value('value') ?? '0126278482';
                ?>
                <div class="payment-section">
                    <div class="payment-title">Payment Details</div>
                    <div class="payment-grid">
                        <div class="payment-item">
                            <div class="payment-item-label">Bank</div>
                            <div class="payment-item-value">{{ $bankName }}</div>
                        </div>
                        <div class="payment-item">
                            <div class="payment-item-label">Account Name</div>
                            <div class="payment-item-value">{{ $bankAccountName }}</div>
                        </div>
                        <div class="payment-item">
                            <div class="payment-item-label">Account Number</div>
                            <div class="payment-item-value">{{ $bankAccountNumber }}</div>
                        </div>
                    </div>
                </div>

                @if($quotation->notes)
                <div class="notes-section">
                    <div class="notes-title">Notes</div>
                    <div class="notes-content">{{ $quotation->notes }}</div>
                </div>
                @endif

                <p>Our team will contact you to discuss this quotation and answer any questions you may have.</p>

                <a href="{{ url('/') }}" class="btn">View Our Services</a>
            </div>

            <div class="footer">
                <div class="footer-tagline">Powering your world with innovative solutions</div>
                <div class="footer-contact">
                    {{ $businessName ?? 'Envoy Electricals' }} | {{ $businessEmail ?? 'envoyelectricals@gmail.com' }} | {{ $businessPhone ?? '+234 809 708 9259' }} | {{ $businessAddress ?? 'Shop 1, Peace Avenue Junction, Futa Southgate Rd, Akure' }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>