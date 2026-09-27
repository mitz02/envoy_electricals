<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation - {{ $quotation->ref_id }}</title>
    <style>
        @page { margin: 20mm 15mm; }
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 10px; 
            line-height: 1.5; 
            color: #1f2937; 
            background: white;
        }
        /* Header - Logo left, Invoice title right */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #1e3a5f;
        }
        .company-info { flex: 1; }
        .company-logo {
            font-size: 26px;
            font-weight: 800;
            color: #0D1527;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }
        .company-tagline {
            color: #1e3a5f;
            font-size: 11px;
            font-weight: 500;
            margin-bottom: 12px;
        }
        .company-details {
            font-size: 9px;
            color: #6b7280;
            line-height: 1.6;
        }
        .company-details div { margin: 2px 0; }
        .invoice-header { text-align: right; flex: 1; }
        .invoice-title {
            font-size: 28px;
            font-weight: 800;
            color: #1e3a5f;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }
        .invoice-meta {
            font-size: 10px;
            color: #6b7280;
            text-align: right;
        }
        .invoice-meta div { margin: 3px 0; }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-draft { background: #fef3c7; color: #92400e; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-viewed { background: #dbeafe; color: #1e40af; }
        .status-accepted { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-expired { background: #fef3c7; color: #92400e; }
        .status-converted { background: #d1fae5; color: #065f46; }

        /* Bill To & Details */
        .details-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            gap: 30px;
        }
        .detail-block { flex: 1; }
        .detail-label {
            font-size: 8px;
            font-weight: 700;
            color: #1e3a5f;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 10px;
            padding-bottom: 4px;
            border-bottom: 1px solid #1e3a5f;
        }
        .detail-content {
            font-size: 10px;
            color: #374151;
            line-height: 1.8;
        }
        .detail-content strong { color: #1f2937; }
        .detail-row { margin: 4px 0; }
        .detail-row-label { color: #6b7280; font-size: 9px; font-weight: 500; }
        .detail-row-value { color: #1f2937; font-weight: 500; }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9.5px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 6px;
            text-align: left;
        }
        .items-table th {
            background: #1e3a5f;
            color: white;
            font-weight: 600;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .items-table td { color: #374151; }
        .items-table td.number { text-align: right; font-variant-numeric: tabular-nums; }
        .items-table td.center { text-align: center; }
        .items-table tr:nth-child(even) td { background: #f9fafb; }
        .items-table tr:last-child td { border-bottom: 2px solid #1e3a5f; }

        /* Financial Summary - Right aligned */
        .financial-summary {
            width: 100%;
            max-width: 320px;
            margin-left: auto;
            margin-bottom: 30px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }
        .summary-row:last-child { border-bottom: none; }
        .summary-label { color: #6b7280; font-weight: 500; }
        .summary-value { color: #1f2937; font-weight: 600; font-variant-numeric: tabular-nums; }
        .summary-row.total {
            border-top: 2px solid #1e3a5f;
            border-bottom: none;
            margin-top: 8px;
            padding-top: 14px;
            font-size: 12px;
            font-weight: 800;
        }
        .summary-row.total .summary-label { color: #1e3a5f; }
        .summary-row.total .summary-value { color: #1e3a5f; }
        .summary-row.due .summary-value { color: #dc2626; font-weight: 700; }

        /* Payment Details */
        .payment-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
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
            gap: 20px;
        }
        .payment-item { flex: 1; }
        .payment-item-label {
            font-size: 8px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .payment-item-value {
            font-size: 10px;
            color: #1f2937;
            font-weight: 500;
        }

        /* Notes */
        .notes-section {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 25px;
        }
        .notes-title {
            font-size: 8px;
            font-weight: 700;
            color: #92400e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .notes-content {
            font-size: 9.5px;
            color: #78350f;
            line-height: 1.6;
            white-space: pre-line;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 8px;
            line-height: 1.8;
        }
        .footer-tagline {
            color: #1e3a5f;
            font-size: 10px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .footer-contact { font-size: 8px; }
    </style>
</head>
<body>
    <?php
        // Fetch settings dynamically
        $businessName = \App\Models\Setting::where('key', 'business.name')->value('value') ?? 'Envoy Electricals';
        $businessEmail = \App\Models\Setting::where('key', 'business.email')->value('value') ?? 'envoyelectricals@gmail.com';
        $businessPhone = \App\Models\Setting::where('key', 'business.phone')->value('value') ?? '+234 809 708 9259';
        $businessAddress = \App\Models\Setting::where('key', 'business.address')->value('value') ?? 'Shop 1, Peace Avenue Junction, Futa Southgate Rd, Akure';
        
        $bankName = \App\Models\Setting::where('key', 'bank.bank_name')->value('value') ?? 'Wema Bank';
        $bankAccountName = \App\Models\Setting::where('key', 'bank.account_name')->value('value') ?? 'Envoy Electricals';
        $bankAccountNumber = \App\Models\Setting::where('key', 'bank.account_number')->value('value') ?? '0126278482';
    ?>

    <!-- HEADER -->
    <div class="header">
        <div class="company-info">
            <div class="company-logo">{{ $businessName }}</div>
            <div class="company-tagline">Powering your world with innovative solutions</div>
            <div class="company-details">
                <div>{{ $businessEmail }}</div>
                <div>{{ $businessAddress }}</div>
                <div>Tel: {{ $businessPhone }}</div>
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

    <!-- BILL TO & DETAILS -->
    <div class="details-section">
        <div class="detail-block">
            <div class="detail-label">Bill To</div>
            <div class="detail-content">
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
        </div>

        <div class="detail-block">
            <div class="detail-label">Details</div>
            <div class="detail-content">
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
            <?php 
                $discountDisplay = '—';
                // Check if item has discount (we could add discount field to items table if needed)
                // For now, show — as in the reference
            ?>
            <tr>
                <td>
                    <strong>{{ $item->item }}</strong>
                    @if($item->description)
                    <div style="font-size: 8px; color: #6b7280; margin-top: 2px;">{{ $item->description }}</div>
                    @endif
                </td>
                <td class="center">{{ $item->quantity }} {{ $item->unit ?: 'pcs' }}</td>
                <td class="number">₦{{ number_format($item->unit_price, 0) }}</td>
                <td class="center">{{ $discountDisplay }}</td>
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
        $amountPaid = 0; // Would need payment tracking
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

    <!-- NOTES -->
    @if($quotation->notes)
    <div class="notes-section">
        <div class="notes-title">Notes</div>
        <div class="notes-content">{{ $quotation->notes }}</div>
    </div>
    @endif

    <!-- FOOTER -->
    <div class="footer">
        <div class="footer-tagline">Powering your world with innovative solutions</div>
        <div class="footer-contact">
            {{ $businessName }} | {{ $businessEmail }} | {{ $businessPhone }} | {{ $businessAddress }}
        </div>
    </div>
</body>
</html>