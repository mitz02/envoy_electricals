<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $sale->invoice_no }}</title>
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

        .invoice-title {
            text-align: right;
        }

        .invoice-title h2 {
            font-size: 28px;
            font-weight: 900;
            color: #0d1527;
            letter-spacing: 0.02em;
            margin-bottom: 8px;
        }

        .invoice-title .invoice-no {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            font-family: monospace;
        }

        .invoice-meta {
            margin-top: 12px;
            font-size: 10px;
            color: #64748b;
        }

        .invoice-meta div {
            margin: 3px 0;
        }

        .invoice-meta span {
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

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-void {
            background: #fee2e2;
            color: #991b1b;
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
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <img src="{{ asset('envoy_images/logo.png') }}" alt="Envoy Electricals" class="logo">
                <div class="company-details">
                    <h1>{{ $company['name'] }}</h1>
                    <div class="tagline">Electrical & Solar Solutions</div>
                </div>
            </div>

            <div class="invoice-title">
                <h2>INVOICE</h2>
                <div class="invoice-no">{{ $sale->invoice_no }}</div>
                <div class="invoice-meta">
                    <div><span>Reference:</span> {{ $sale->ref_id }}</div>
                    <div><span>Date:</span> {{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</div>
                    <div><span>Salesperson:</span> {{ $sale->salesperson?->name ?? '—' }}</div>
                    @if($sale->store)
                    <div><span>Branch:</span> {{ $sale->store->name }}</div>
                    @endif
                </div>

                <span class="status-badge status-{{ $sale->status }}">
                    {{ ucfirst($sale->status) }}
                </span>
            </div>
        </div>

        <!-- Details Grid -->
        <div class="details-grid">
            <div class="detail-box">
                <div class="detail-label">Invoice Date</div>
                <div class="detail-value">{{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</div>
            </div>
            <div class="detail-box">
                <div class="detail-label">Due Date</div>
                <div class="detail-value">On Receipt</div>
            </div>
            <div class="detail-box">
                <div class="detail-label">Payment Method</div>
                <div class="detail-value">{{ ucfirst(str_replace('_', ' ', $sale->payment_method ?? '—')) }}</div>
            </div>
            <div class="detail-box">
                <div class="detail-label">Branch</div>
                <div class="detail-value">{{ $sale->store?->name ?? '—' }}</div>
            </div>
        </div>

        <!-- Billed To -->
        <div class="billed-section">
            <div class="billed-row">
                <div class="billed-col">
                    <div class="billed-label">Billed To</div>
                    <div class="billed-content">
                        <div class="name">{{ $sale->customer?->name ?? 'Walk-in Customer' }}</div>
                        @if($sale->customer)
                            @if($sale->customer->phone)
                            <div>{{ $sale->customer->phone }}</div>
                            @endif
                            @if($sale->customer->email)
                            <div>{{ $sale->customer->email }}</div>
                            @endif
                            @if($sale->customer->address)
                            <div>{{ $sale->customer->address }}</div>
                            @endif
                            @if($sale->customer->outstanding > $sale->balance)
                            <div class="outstanding">
                                Also owes {{ \App\Helpers\Currency::format($sale->customer->outstanding - $sale->balance) }} from other invoices
                            </div>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="billed-col">
                    <div class="billed-label">Sold By</div>
                    <div class="billed-content">
                        <div class="name">{{ $sale->salesperson?->name ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
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
                @foreach($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <div class="item-name">{{ $item->product?->name ?? $item->description }}</div>
                        @if($item->product?->sku)
                        <div class="item-sku">{{ $item->product->sku }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ \App\Helpers\Currency::format($item->unit_price) }}</td>
                    <td class="text-right">{{ \App\Helpers\Currency::format($item->total) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="value">{{ \App\Helpers\Currency::format($sale->subtotal) }}</td>
                </tr>
                @if($sale->discount > 0)
                <tr class="discount">
                    <td class="label">Discount</td>
                    <td class="value">-{{ \App\Helpers\Currency::format($sale->discount) }}</td>
                </tr>
                @endif
                @if($sale->tax > 0)
                <tr>
                    <td class="label">Tax ({{ $sale->tax_rate }}%)</td>
                    <td class="value">{{ \App\Helpers\Currency::format($sale->tax) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td class="label">Total</td>
                    <td class="value">{{ \App\Helpers\Currency::format($sale->total) }}</td>
                </tr>
                <tr class="paid-row">
                    <td class="label">Paid</td>
                    <td class="value">{{ \App\Helpers\Currency::format($sale->amount_paid) }}</td>
                </tr>
                <tr class="balance-row {{ $sale->balance <= 0 ? 'balance-zero' : '' }}">
                    <td class="label">Balance Due</td>
                    <td class="value">{{ \App\Helpers\Currency::format($sale->balance) }}</td>
                </tr>
            </table>
        </div>

        <!-- Notes & Terms -->
        <div class="notes-terms">
            <div class="notes-box">
                <div class="box-title">Notes</div>
                <p>{{ $sale->remarks ?: '—' }}</p>
            </div>

            <div class="terms-box">
                <div class="box-title">Terms & Conditions</div>
                <p>
                    Payment is due on receipt. Goods purchased are not returnable without the original receipt and within 7 days of sale.
                    All solar installations carry manufacturer warranties as specified. Title to goods remains with Envoy Electricals until full payment is received.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>{{ $company['name'] }}</strong></p>
            @if($company['phone'] || $company['email'])
            <p>
                @if($company['phone'])
                Tel: {{ $company['phone'] }}
                @endif
                @if($company['phone'] && $company['email'])
                &nbsp;&middot;&nbsp;
                @endif
                @if($company['email'])
                Email: {{ $company['email'] }}
                @endif
            </p>
            @endif
            <p>Generated on {{ now()->format('d M Y H:i') }} · Invoice Ref: {{ $sale->ref_id }}</p>
        </div>
    </div>
</body>
</html>