<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Quotation {{ $quotation->ref_id }}</title>

    <style>

        /*
        |--------------------------------------------------------------------------
        | PAGE
        |--------------------------------------------------------------------------
        */

        @page {
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #222222;
            background: #ffffff;
            line-height: 1.35;
        }

        /*
        |--------------------------------------------------------------------------
        | MAIN PAGE CONTAINER
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Do NOT use width:100% together with left/right padding here.
        | Dompdf can push the right side outside the printable area.
        |
        */

        .invoice {
            width: auto;
            margin-left: 14mm;
            margin-right: 14mm;
            padding-top: 16mm;
            padding-bottom: 12mm;
        }


        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .header {
            width: 100%;
            border-bottom: 1px solid #dddddd;
            padding-bottom: 18px;
            margin-bottom: 4px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .header-left {
            width: 62%;
            vertical-align: top;
            padding-right: 10px;
        }

        .header-right {
            width: 38%;
            vertical-align: top;
            text-align: right;
            padding-left: 10px;
        }

        .logo {
            width: 145px;
            height: auto;
            margin-bottom: 5px;
        }

        .company-name {
            font-size: 19px;
            font-weight: bold;
            color: #0D1527;
            margin-bottom: 3px;
        }

        .company-info {
            font-size: 10px;
            color: #333333;
            line-height: 1.5;
        }

        .quotation-title {
            font-size: 25px;
            line-height: 1;
            font-weight: bold;
            color: #0D1527;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .quotation-number {
            font-size: 10px;
            color: #333333;
            margin-bottom: 10px;
            white-space: nowrap;
        }

        .status {
            font-size: 9px;
            font-weight: bold;
            color: #333333;
            text-transform: uppercase;
        }


        /*
        |--------------------------------------------------------------------------
        | BILL TO / DETAILS
        |--------------------------------------------------------------------------
        */

        .top-information {
            width: 100%;
            margin-bottom: 24px;
        }

        .information-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .bill-to {
            width: 58%;
            vertical-align: top;
            padding-right: 15px;
        }

        .quotation-details {
            width: 42%;
            vertical-align: top;
            text-align: right;
            padding-left: 15px;
        }

        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #444444;
            margin-bottom: 6px;
        }

        .customer-name {
            font-size: 11px;
            font-weight: bold;
            color: #222222;
            margin-bottom: 2px;
        }

        .customer-info {
            font-size: 10px;
            color: #333333;
            line-height: 1.5;
        }

        .details-label {
            font-weight: bold;
            color: #333333;
        }

        .details-row {
            margin-bottom: 4px;
            font-size: 10px;
            white-space: nowrap;
        }


        /*
        |--------------------------------------------------------------------------
        | ITEMS
        |--------------------------------------------------------------------------
        */

        .items {
            width: 100%;
            margin-top: 10px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .items-table th {
            padding: 7px 6px;
            text-align: left;
            font-size: 9px;
            font-weight: normal;
            color: #999999;
            border-bottom: 1px solid #dddddd;
        }

        .items-table th.item {
            width: 34%;
        }

        .items-table th.qty {
            width: 10%;
            text-align: center;
        }

        .items-table th.price {
            width: 22%;
            text-align: right;
        }

        .items-table th.total {
            width: 22%;
            text-align: right;
        }

        .items-table td {
            padding: 7px 6px;
            font-size: 10px;
            color: #333333;
            border-bottom: 1px solid #eeeeee;
            vertical-align: middle;
            overflow: hidden;
        }

        .items-table td.qty {
            text-align: center;
        }

        .items-table td.price {
            text-align: right;
            white-space: nowrap;
        }

        .items-table td.total {
            text-align: right;
            white-space: nowrap;
        }

        .item-name {
            font-weight: bold;
            color: #333333;
            word-wrap: break-word;
        }

        .item-sku {
            font-size: 8px;
            color: #999999;
            margin-top: 1px;
        }

        .discount-value {
            color: #ff3333;
        }


        /*
        |--------------------------------------------------------------------------
        | BOTTOM SECTION
        |--------------------------------------------------------------------------
        */

        .bottom-section {
            width: 100%;
            margin-top: 18px;
        }

        .bottom-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .bottom-left {
            width: 60%;
            vertical-align: top;
            padding-right: 25px;
        }

        .bottom-right {
            width: 40%;
            vertical-align: top;
            padding-left: 5px;
        }


        /*
        |--------------------------------------------------------------------------
        | PAYMENT DETAILS
        |--------------------------------------------------------------------------
        */

        .payment-details {
            margin-top: 38px;
        }

        .payment-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
            color: #444444;
        }

        .payment-row {
            font-size: 10px;
            margin-bottom: 7px;
            color: #333333;
        }

        .payment-row strong {
            color: #222222;
        }


        /*
        |--------------------------------------------------------------------------
        | TOTALS
        |--------------------------------------------------------------------------
        */

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .totals-table td {
            padding: 5px 0;
            font-size: 10px;
            border-bottom: 1px solid #eeeeee;
        }

        .totals-label {
            width: 55%;
            text-align: left;
            color: #666666;
        }

        .totals-value {
            width: 45%;
            text-align: right;
            font-weight: bold;
            color: #333333;
            white-space: nowrap;
        }

        .grand-total td {
            padding-top: 10px;
            padding-bottom: 10px;
            font-size: 15px;
            font-weight: bold;
            color: #000000;
            background: transparent;
            border: none;
            border-bottom: 2px solid #0083f4;
        }

        .grand-total .totals-value {
            color: #000000;
        }

        .amount-paid .totals-value {
            color: #555555;
        }

        .amount-due .totals-value {
            color: #555555;
        }


        /*
        |--------------------------------------------------------------------------
        | DESCRIPTION
        |--------------------------------------------------------------------------
        */

        .description {
            width: 100%;
            margin-top: 0;
            margin-bottom: 16px;
            text-align: center;
            font-size: 15px;
            color: #333333;
            line-height: 1.6;
            white-space: pre-line;
        }


        /*
        |--------------------------------------------------------------------------
        | NOTES
        |--------------------------------------------------------------------------
        */

        .notes {
            margin-top: 42px;
            padding-top: 12px;
            border-top: 1px solid #dddddd;
        }

        .notes-text {
            font-size: 9px;
            color: #333333;
            line-height: 1.5;
            white-space: pre-line;
        }


        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        .footer {
            margin-top: 7px;
            padding-top: 8px;
            border-top: 1px solid #eeeeee;
            font-size: 8px;
            color: #333333;
        }


        /*
        |--------------------------------------------------------------------------
        | WATERMARK
        |--------------------------------------------------------------------------
        */

        .watermark {
            position: fixed;
            top: 40%;
            left: 35%;
            width: 250px;
            opacity: 0.05;
        }


        /*
        |--------------------------------------------------------------------------
        | PRINT
        |--------------------------------------------------------------------------
        */

        @media print {

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

        }

    </style>

</head>


<body>


<div class="invoice">


<!-- =========================================================
         WATERMARK
     ========================================================== -->

    <img src="{{ public_path('envoy_images/logo.png') }}" class="watermark" style="top: 15%; left: 10%;" alt="">
    <img src="{{ public_path('envoy_images/logo.png') }}" class="watermark" style="top: 15%; left: 45%;" alt="">
    <img src="{{ public_path('envoy_images/logo.png') }}" class="watermark" style="top: 15%; left: 75%;" alt="">
    <img src="{{ public_path('envoy_images/logo.png') }}" class="watermark" style="top: 40%; left: 10%;" alt="">
    <img src="{{ public_path('envoy_images/logo.png') }}" class="watermark" style="top: 40%; left: 45%;" alt="">
    <img src="{{ public_path('envoy_images/logo.png') }}" class="watermark" style="top: 40%; left: 75%;" alt="">
    <img src="{{ public_path('envoy_images/logo.png') }}" class="watermark" style="top: 65%; left: 10%;" alt="">
    <img src="{{ public_path('envoy_images/logo.png') }}" class="watermark" style="top: 65%; left: 45%;" alt="">
    <img src="{{ public_path('envoy_images/logo.png') }}" class="watermark" style="top: 65%; left: 75%;" alt="">
    <img src="{{ public_path('envoy_images/logo.png') }}" class="watermark" style="top: 85%; left: 10%;" alt="">
    <img src="{{ public_path('envoy_images/logo.png') }}" class="watermark" style="top: 85%; left: 45%;" alt="">
    <img src="{{ public_path('envoy_images/logo.png') }}" class="watermark" style="top: 85%; left: 75%;" alt="">


    <!-- =========================================================
         HEADER
    ========================================================== -->

    <div class="header">

        <table class="header-table">

            <tr>


                <!-- COMPANY INFORMATION -->

                <td class="header-left">

                    <img
                        src="{{ public_path('envoy_images/logo.png') }}"
                        class="logo"
                        alt="{{ $company['name'] ?? 'Envoy Electricals' }}"
                    >

                    <div class="company-info">

                        {{ $company['email'] ?? 'envoyelectricals@gmail.com' }}

                        <br>

                        {{ $company['address'] ?? 'Shop 1, Peace Avenue Junction, opp Goddy Royal Hotel, Futa Southgate Road, Akure' }}

                    </div>

                </td>


                <!-- QUOTATION INFORMATION -->

                <td class="header-right">

                    <div class="quotation-title">
                        Quotation
                    </div>

                    <div class="quotation-number">
                        #{{ $quotation->ref_id }}
                    </div>

                    <div class="status">
                        {{ strtoupper($quotation->status ?? 'DRAFT') }}
                    </div>

                </td>

            </tr>

        </table>

    </div>


    <!-- =========================================================
         DESCRIPTION
    ========================================================== -->

    @if($quotation->description)

        <div class="description" style="margin-top: -12px; padding-top: 0;">
            {{ $quotation->description }}
        </div>

    @endif


    <!-- =========================================================
         BILL TO + DETAILS
    ========================================================== -->

    <div class="top-information">

        <table class="information-table">

            <tr>


                <!-- BILL TO -->

                <td class="bill-to">

                    <div class="section-title">
                        Bill To
                    </div>


                    <div class="customer-name">

                        {{ $quotation->customer_name }}

                    </div>


                    @if($quotation->customer_company)

                        <div class="customer-info">

                            {{ $quotation->customer_company }}

                        </div>

                    @endif


                    @if($quotation->customer_phone)

                        <div class="customer-info">

                            {{ $quotation->customer_phone }}

                        </div>

                    @endif


                    @if($quotation->customer_email)

                        <div class="customer-info">

                            {{ $quotation->customer_email }}

                        </div>

                    @endif


                    @if($quotation->customer_address)

                        <div class="customer-info">

                            {{ $quotation->customer_address }}

                        </div>

                    @endif

                </td>


                <!-- DETAILS -->

                <td class="quotation-details">

                    <div class="section-title">
                        Details
                    </div>


                    <div class="details-row">

                        <span class="details-label">
                            Issue Date:
                        </span>

                        {{ \Carbon\Carbon::parse($quotation->quotation_date)->format('d/m/Y') }}

                    </div>


                    @if($quotation->valid_until)

                        <div class="details-row">

                            <span class="details-label">
                                Valid Until:
                            </span>

                            {{ \Carbon\Carbon::parse($quotation->valid_until)->format('d/m/Y') }}

                        </div>

                    @endif


                    @if($quotation->job_type)

                        <div class="details-row">

                            <span class="details-label">
                                Job Type:
                            </span>

                            {{ $quotation->job_type }}

                        </div>

                    @endif

                </td>

            </tr>

        </table>

    </div>


    <!-- =========================================================
         ITEMS
    ========================================================== -->

    <div class="items">

        <table class="items-table">


            <thead>

                <tr>

                    <th class="item">
                        Item
                    </th>

                    <th class="qty">
                        Qty
                    </th>

                    <th class="price">
                        Price
                    </th>

                    <th class="total">
                        Total
                    </th>

                </tr>

            </thead>


            <tbody>


                @foreach($quotation->items as $index => $item)

                <tr>


                    <!-- ITEM -->

                    <td>

                        <div class="item-name">

                            {{ $item->item }}

                        </div>


                        @if($item->description)

                            <div class="item-sku">

                                {{ $item->description }}

                            </div>

                        @endif

                    </td>


                    <!-- QUANTITY -->

                    <td class="qty">

                        {{ $item->quantity }} {{ $item->unit ?: 'pcs' }}

                    </td>


                    <!-- PRICE -->

                    <td class="price">

                        &#8358;{{ number_format($item->unit_price, 0, '.', ',') }}

                    </td>


                    <!-- TOTAL -->

                    <td class="total">

                        &#8358;{{ number_format($item->total, 0, '.', ',') }}

                    </td>


                </tr>


                @endforeach


            </tbody>

        </table>

    </div>


    <!-- =========================================================
         PAYMENT DETAILS + TOTALS
    ========================================================== -->

    <div class="bottom-section">

        <table class="bottom-table">

            <tr>


                <!-- PAYMENT DETAILS -->

                <td class="bottom-left">

                    <div class="payment-details">

                        <div class="payment-title">
                            Payment Details
                        </div>


                        <div class="payment-row">

                            <strong>Bank:</strong>

                            {{ $company['bank_name'] ?? 'Wema Bank' }}

                        </div>


                        <div class="payment-row">

                            <strong>Account Name:</strong>

                            {{ $company['account_name'] ?? 'Envoy Electricals' }}

                        </div>


                        <div class="payment-row">

                            <strong>Account Number:</strong>

                            {{ $company['account_number'] ?? '0126278482' }}

                        </div>

                    </div>

                </td>


                <!-- TOTALS -->

                <td class="bottom-right">

                    <?php
                        $subtotal = $quotation->items->sum('total');
                        $discount = $quotation->discount ?? 0;
                        $tax = $quotation->tax ?? 0;
                        $otherCharges = $quotation->other_charges ?? 0;
                        $grandTotal = $subtotal + $tax + $otherCharges;
                    ?>

                    <table class="totals-table">


                        <!-- SUBTOTAL -->

                        <tr>

                            <td class="totals-label">
                                Subtotal
                            </td>

                            <td class="totals-value">

                                &#8358;{{ number_format($subtotal, 0, '.', ',') }}

                            </td>

                        </tr>


                        <!-- TAX -->

                        @if($tax > 0)

                            <tr>

                                <td class="totals-label">

                                    Tax ({{ $quotation->tax_rate ?? 0 }}%)

                                </td>

                                <td class="totals-value">

                                    &#8358;{{ number_format($tax, 0, '.', ',') }}

                                </td>

                            </tr>

                        @endif


                        <!-- OTHER CHARGES -->

                        @if($otherCharges > 0)

                            <tr>

                                <td class="totals-label">
                                    Other Charges
                                </td>

                                <td class="totals-value">

                                    &#8358;{{ number_format($otherCharges, 0, '.', ',') }}

                                </td>

                            </tr>

                        @endif


                        <!-- GRAND TOTAL -->

                        <tr class="grand-total">

                            <td>
                                Grand Total
                            </td>

                            <td class="totals-value">

                                &#8358;{{ number_format($grandTotal, 0, '.', ',') }}

                            </td>

                        </tr>


                        <!-- AMOUNT PAID -->

                        <tr class="amount-paid">

                            <td class="totals-label">
                                Amount Paid
                            </td>

                            <td class="totals-value">

                                &#8358;0

                            </td>

                        </tr>


                        <!-- AMOUNT DUE -->

                        <tr class="amount-due">

                            <td class="totals-label">
                                Amount Due
                            </td>

                            <td class="totals-value">

                                &#8358;{{ number_format($grandTotal, 0, '.', ',') }}

                            </td>

                        </tr>


                    </table>

                </td>

            </tr>

        </table>

    </div>


    <!-- =========================================================
         NOTES
    ========================================================== -->

    <div class="notes">

        <div class="notes-text">

            <strong>Notes:</strong>

            @if($quotation->notes)
                {{ $quotation->notes }}
            @else
                This quotation is valid for 30 days from the issue date.
            @endif

        </div>

    </div>


    <!-- =========================================================
         FOOTER
    ========================================================== -->

    <div class="footer">

        {{ $company['footer'] ?? 'powering your world with innovative solutions' }}

    </div>


</div>


</body>

</html>