<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation {{ $quotation->ref_id }}</title>
    <style>
        @page {
            margin: 20mm 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #1e293b;
            background: #ffffff;
        }

        .invoice-container {
            max-width: 100%;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #0d1527;
        }

        .company-info {
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }

        .logo {
            width: 140px;
            height: 140px;
            object-fit: contain;
        }

        .company-details h1 {
            font-size: 22px;
            font-weight: 900;
            color: #0d1527;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .company-details .tagline {
            font-size: 10px;
            color: #facc15;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .company-details p {
            font-size: 10px;
            color: #64748b;
            line-height: 1.6;
            margin: 2px 0;
        }

        .quotation-title {
            text-align: right;
        }

        .quotation-title h2 {
            font-size: 28px;
            font-weight: 900;
            color: #0d1527;
            letter-spacing: 0.02em;
            margin-bottom: 8px;
        }

        .quotation-title .quotation-no {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            font-family: monospace;
        }

        .quotation-meta {
            margin-top: 12px;
            font-size: 10px;
            color: #64748b;
        }

        .quotation-meta div {
            margin: 3px 0;
        }

        .quotation-meta span {
            font-weight: 600;
            color: #0d1527;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 8px;
        }

        .status-draft {
            background: #fef3c7;
            color: #92400e;
        }

        .status-sent {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-viewed {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-accepted {
            background: #d1fae5;
            color: #065f46;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-expired {
            background: #fef3c7;
            color: #92400e;
        }

        .status-converted {
            background: #d1fae5;
            color: #065f46;
        }

        /* Billed To / Details Grid */
        .details-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 25px 0;
        }

        .detail-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
        }

        .detail-label {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #94a3b8;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 11px;
            font-weight: 600;
            color: #0d1527;
            line-height: 1.4;
        }

        /* Billed To Section */
        .billed-section {
            margin: 25px 0;
        }

        .billed-row {
            display: flex;
            gap: 40px;
        }

        .billed-col {
            flex: 1;
        }

        .billed-label {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #94a3b8;
            margin-bottom: 6px;
        }

        .billed-content {
            font-size: 11px;
            color: #1e293b;
            line-height: 1.6;
        }

        .billed-content .name {
            font-weight: 700;
            font-size: 12px;
            color: #0d1527;
            margin-bottom: 4px;
        }

        .billed-content .outstanding {
            font-size: 9px;
            color: #b45309;
            background: #fef3c7;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 8px;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 10px;
        }

        .items-table th {
            background: #0d1527;
            color: #facc15;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 8px;
            padding: 10px 8px;
            text-align: left;
        }

        .items-table th:last-child,
        .items-table th:nth-last-child(2),
        .items-table th:nth-last-child(3),
        .items-table th:nth-last-child(4) {
            text-align: right;
        }

        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .items-table td:first-child {
            color: #94a3b8;
            font-weight: 500;
        }

        .items-table .item-name {
            font-weight: 600;
            color: #0d1527;
            margin-bottom: 2px;
        }

        .items-table .item-sku {
            font-size: 8px;
            color: #64748b;
            font-family: monospace;
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .text-center {
            text-align: center;
        }

        /* Totals */
        .totals-section {
            margin-top: 20px;
            padding-left: 50%;
        }

        .totals-table {
            width: 100%;
            font-size: 10px;
        }

        .totals-table td {
            padding: 6px 12px;
        }

        .totals-table .label {
            color: #64748b;
            text-align: right;
            font-weight: 500;
        }

        .totals-table .value {
            color: #0d1527;
            font-weight: 600;
            text-align: right;
            white-space: nowrap;
        }

        .totals-table .discount .value {
            color: #dc2626;
        }

        .totals-table .total-row td {
            border-top: 2px solid #0d1527;
            font-size: 13px;
            font-weight: 800;
            color: #0d1527;
            padding-top: 12px;
        }

        .totals-table .paid-row .value {
            color: #16a34a;
        }

        .totals-table .balance-row {
            background: #fef3c7;
        }

        .totals-table .balance-row .value {
            color: #b45309;
            font-weight: 800;
        }

        .totals-table .balance-row td {
            padding: 10px 12px;
        }

        .totals-table .balance-zero .value {
            color: #16a34a;
        }

        /* Notes & Terms */
        .notes-terms {
            margin-top: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .notes-box,
        .terms-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
        }

        .box-title {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #94a3b8;
            margin-bottom: 8px;
        }

        .notes-box p,
        .terms-box p {
            font-size: 9px;
            color: #475569;
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            line-height: 1.8;
        }

        .footer p {
            margin: 4px 0;
        }

        .footer a {
            color: #64748b;
            text-decoration: none;
        }

        /* Utility */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: monospace; }

        /* Currency prefix */
        .currency { font-family: monospace; }
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
        $currencySymbol = \App\Models\Setting::where('key', 'currency.symbol')->value('value') ?? '₦';
    ?>

    <!-- HEADER -->
    <div class="header">
        <div class="company-info">
            <div class="company-logo">
                <img src="/envoy_images/logo.png" alt="{{ $businessName }}" class="logo">
                <div class="company-details">
                    <h1>{{ $businessName }}</h1>
                    <div class="tagline">Powering your world with innovative solutions</div>
                </div>
            </div>
        </div>
        <div class="invoice-header">
            <h2 class="invoice-title">QUOTATION</h2>
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
                <th style="width: 5%;">#</th>
                <th style="width: 40%;">Item</th>
                <th style="width: 10%;" class="text-center">Qty</th>
                <th style="width: 15%;" class="text-right">Unit Price</th>
                <th style="width: 15%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <div class="item-name">{{ $item->item }}</div>
                    @if($item->description)
                    <div class="item-sku">{{ $item->description }}</div>
                    @endif
                </td>
                <td class="text-center">{{ $item->quantity }} {{ $item->unit ?: 'pcs' }}</td>
                <td class="text-right">{{ $currencySymbol }}{{ number_format($item->unit_price, 0) }}</td>
                <td class="text-right"><strong>{{ $currencySymbol }}{{ number_format($item->total, 0) }}</strong></td>
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
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal</td>
                <td class="value">{{ $currencySymbol }}{{ number_format($subtotal, 0) }}</td>
            </tr>
            @if($discount > 0)
            <tr class="discount">
                <td class="label">Discount</td>
                <td class="value">-{{ $currencySymbol }}{{ number_format($discount, 0) }}</td>
            </tr>
            @endif
            @if($quotation->tax > 0)
            <tr>
                <td class="label">Tax / VAT</td>
                <td class="value">+{{ $currencySymbol }}{{ number_format($quotation->tax, 0) }}</td>
            </tr>
            @endif
            @if($quotation->other_charges > 0)
            <tr>
                <td class="label">Other Charges</td>
                <td class="value">+{{ $currencySymbol }}{{ number_format($quotation->other_charges, 0) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td class="label">Total</td>
                <td class="value">{{ $currencySymbol }}{{ number_format($grandTotal, 0) }}</td>
            </tr>
            <tr class="paid-row">
                <td class="label">Amount Paid</td>
                <td class="value">{{ $currencySymbol }}{{ number_format(0, 0) }}</td>
            </tr>
            <tr class="balance-row {{ $amountDue <= 0 ? 'balance-zero' : '' }}">
                <td class="label">Balance Due</td>
                <td class="value">{{ $currencySymbol }}{{ number_format($grandTotal, 0) }}</td>
            </tr>
        </table>
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

    @if($quotation->notes)
    <div class="notes-section">
        <div class="box-title">Notes</div>
        <p>{{ $quotation->notes }}</p>
    </div>
    @endif

    <!-- FOOTER -->
    <div class="footer">
        <div class="footer-tagline">Powering your world with innovative solutions</div>
        <div class="footer-contact">
            {{ $businessName }} | {{ $businessEmail }} | {{ $businessPhone }}
        </div>
    </div>
</body>
</html>