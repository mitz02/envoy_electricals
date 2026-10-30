<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Receipt - {{ $receipt->ref_id }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 24px 12px;
            color: #1e293b;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #072b56;
            padding: 28px 24px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 0.5px;
            font-weight: 700;
        }
        .header p {
            margin: 6px 0 0;
            font-size: 13px;
            color: #93c5fd;
            font-style: italic;
        }
        .content {
            padding: 32px 28px;
        }
        .badge {
            display: inline-block;
            background: #ecfdf5;
            color: #059669;
            font-weight: 600;
            font-size: 12px;
            padding: 4px 12px;
            border-radius: 9999px;
            margin-bottom: 16px;
            border: 1px solid #a7f3d0;
        }
        .receipt-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #cbd5e1;
            font-size: 14px;
        }
        .receipt-row:last-child {
            border-bottom: none;
        }
        .row-label {
            color: #64748b;
            font-weight: 500;
        }
        .row-val {
            color: #0f172a;
            font-weight: 700;
            text-align: right;
        }
        .amount-highlight {
            background: #eff6ff;
            border-left: 4px solid #0284c7;
            padding: 14px 18px;
            border-radius: 0 8px 8px 0;
            margin: 18px 0;
        }
        .amount-val {
            font-size: 24px;
            font-weight: 800;
            color: #0369a1;
        }
        .amount-words {
            font-size: 13px;
            color: #475569;
            margin-top: 4px;
            font-style: italic;
        }
        .btn {
            display: inline-block;
            background-color: #0284c7;
            color: #ffffff !important;
            padding: 14px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            text-align: center;
            margin: 20px 0 10px;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px 24px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ENVOY ELECTRICALS</h1>
            <p>Powering your World with Innovative Solutions</p>
        </div>

        <div class="content">
            <span class="badge">&#10003; Payment Received & Confirmed</span>
            <h2 style="margin: 0 0 12px; font-size: 18px; color: #0f172a;">
                Dear {{ $receipt->customer_name }},
            </h2>
            <p style="font-size: 14px; line-height: 1.6; color: #475569; margin: 0 0 20px;">
                Thank you for your payment. Please find your official Cash Receipt details below. A printable/downloadable copy of the official receipt has also been attached to this email.
            </p>

            <div class="amount-highlight">
                <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #0284c7; font-weight: 600;">Amount Received</div>
                <div class="amount-val">&#8358;{{ number_format((float) $receipt->amount, 2) }}</div>
                <div class="amount-words">{{ $receipt->amount_in_words }}</div>
            </div>

            <div class="receipt-card">
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;">Receipt Number:</td>
                        <td style="padding: 6px 0; font-weight: 700; text-align: right; color: #072b56;">{{ $receipt->ref_id }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;">Date:</td>
                        <td style="padding: 6px 0; font-weight: 600; text-align: right;">{{ \Carbon\Carbon::parse($receipt->receipt_date)->format('F d, Y') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;">Payment For:</td>
                        <td style="padding: 6px 0; font-weight: 600; text-align: right;">{{ $receipt->payment_for }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;">Payment Method:</td>
                        <td style="padding: 6px 0; font-weight: 600; text-align: right;">{{ $receipt->payment_method }}</td>
                    </tr>
                </table>
            </div>

            <div style="text-align: center;">
                <a href="{{ $receipt->public_url }}" class="btn">
                    View & Download Official E-Receipt
                </a>
                <p style="font-size: 12px; color: #94a3b8; margin: 8px 0 0;">
                    Or view online: <a href="{{ $receipt->public_url }}" style="color: #0284c7;">{{ $receipt->public_url }}</a>
                </p>
            </div>
        </div>

        <div class="footer">
            <strong>{{ $company['name'] }}</strong><br>
            {{ $company['address'] }}<br>
            Tel: {{ $company['phone'] }} | Email: {{ $company['email'] }}<br>
            RC: 7464589
        </div>
    </div>
</body>
</html>
