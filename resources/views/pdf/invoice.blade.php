
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Invoice {{ $sale->invoice_no }}</title>

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
            margin-bottom: 28px;
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
            color: #111cff;
            margin-bottom: 3px;
        }

        .company-info {
            font-size: 10px;
            color: #333333;
            line-height: 1.5;
        }

        .invoice-title {
            font-size: 25px;
            line-height: 1;
            font-weight: bold;
            color: #111cff;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .invoice-number {
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

        .invoice-details {
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
            width: 18%;
            text-align: right;
        }

        .items-table th.discount {
            width: 20%;
            text-align: center;
        }

        .items-table th.total {
            width: 18%;
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

        .items-table td.discount {
            text-align: center;
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
            padding-top: 8px;
            padding-bottom: 8px;
            font-size: 14px;
            font-weight: bold;
            color: #111cff;
            border-top: 1px solid #dddddd;
        }

        .grand-total .totals-value {
            color: #111cff;
        }

        .amount-paid .totals-value {
            color: #555555;
        }

        .amount-due .totals-value {
            color: #555555;
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
            opacity: 0.025;
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

    <img
        src="{{ public_path('envoy_images/logo.png') }}"
        class="watermark"
        alt=""
    >


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

                    <div class="company-name">
                        {{ $company['name'] ?? 'Envoy Electricals' }}
                    </div>

                    <div class="company-info">

                        {{ $company['email'] ?? 'envoyelectricals@gmail.com' }}

                        <br>

                        {{ $company['address'] ?? 'Shop 1, Peace Avenue Junction, opp Goddy Royal Hotel, Futa Southgate Road, Akure' }}

                    </div>

                </td>


                <!-- INVOICE INFORMATION -->

                <td class="header-right">

                    <div class="invoice-title">
                        Invoice
                    </div>

                    <div class="invoice-number">
                        #{{ $sale->invoice_no }}
                    </div>

                    <div class="status">
                        {{ strtoupper($sale->status ?? 'DRAFT') }}
                    </div>

                </td>

            </tr>

        </table>

    </div>



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

                        {{ $sale->customer?->name ?? 'Walk-in Customer' }}

                    </div>


                    @if($sale->customer)


                        @if($sale->customer->phone)

                            <div class="customer-info">

                                {{ $sale->customer->phone }}

                            </div>

                        @endif


                        @if($sale->customer->email)

                            <div class="customer-info">

                                {{ $sale->customer->email }}

                            </div>

                        @endif


                        @if($sale->customer->address)

                            <div class="customer-info">

                                {{ $sale->customer->address }}

                            </div>

                        @endif


                    @endif

                </td>



                <!-- DETAILS -->

                <td class="invoice-details">

                    <div class="section-title">
                        Details
                    </div>


                    <div class="details-row">

                        <span class="details-label">
                            Issue Date:
                        </span>

                        {{ \Carbon\Carbon::parse($sale->sale_date)->format('d/m/Y') }}

                    </div>


                    @if($sale->due_date)

                        <div class="details-row">

                            <span class="details-label">
                                Due Date:
                            </span>

                            {{ \Carbon\Carbon::parse($sale->due_date)->format('d/m/Y') }}

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

                    <th class="discount">
                        Discount / Bonus
                    </th>

                    <th class="total">
                        Total
                    </th>

                </tr>

            </thead>


            <tbody>


                @foreach($items as $index => $item)


                    @php

                        $originalAmount =
                            $item->quantity * $item->unit_price;

                        $itemDiscount =
                            max(0, $originalAmount - $item->total);

                    @endphp


                    <tr>


                        <!-- ITEM -->

                        <td>

                            <div class="item-name">

                                {{ $item->product?->name ?? $item->description }}

                            </div>


                            @if($item->product?->sku)

                                <div class="item-sku">

                                    {{ $item->product->sku }}

                                </div>

                            @endif

                        </td>


                        <!-- QUANTITY -->

                        <td class="qty">

                            {{ $item->quantity }}

                        </td>


                        <!-- PRICE -->

                        <td class="price">

                            &#8358;{{ number_format($item->unit_price, 0, '.', ',') }}

                        </td>


                        <!-- DISCOUNT -->

                        <td class="discount">

                            @if($itemDiscount > 0)

                                <span class="discount-value">

                                    -&#8358;{{ number_format($itemDiscount, 0, '.', ',') }}

                                </span>

                            @else

                                -

                            @endif

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

                    <table class="totals-table">


                        <!-- SUBTOTAL -->

                        <tr>

                            <td class="totals-label">
                                Subtotal
                            </td>

                            <td class="totals-value">

                                &#8358;{{ number_format($sale->subtotal, 0, '.', ',') }}

                            </td>

                        </tr>


                        <!-- DISCOUNT -->

                        @if($sale->discount > 0)

                            <tr>

                                <td class="totals-label">
                                    Discount
                                </td>

                                <td class="totals-value">

                                    -&#8358;{{ number_format($sale->discount, 0, '.', ',') }}

                                </td>

                            </tr>

                        @endif


                        <!-- TAX -->

                        @if($sale->tax > 0)

                            <tr>

                                <td class="totals-label">

                                    Tax ({{ $sale->tax_rate }}%)

                                </td>

                                <td class="totals-value">

                                    &#8358;{{ number_format($sale->tax, 0, '.', ',') }}

                                </td>

                            </tr>

                        @endif


                        <!-- TOTAL -->

                        <tr class="grand-total">

                            <td>
                                Total
                            </td>

                            <td class="totals-value">

                                &#8358;{{ number_format($sale->total, 0, '.', ',') }}

                            </td>

                        </tr>


                        <!-- AMOUNT PAID -->

                        <tr class="amount-paid">

                            <td class="totals-label">
                                Amount Paid
                            </td>

                            <td class="totals-value">

                                &#8358;{{ number_format($sale->amount_paid, 0, '.', ',') }}

                            </td>

                        </tr>


                        <!-- AMOUNT DUE -->

                        <tr class="amount-due">

                            <td class="totals-label">
                                Amount Due
                            </td>

                            <td class="totals-value">

                                &#8358;{{ number_format($sale->balance, 0, '.', ',') }}

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

            {{ $sale->remarks ?: 'This estimate covers only the replacement of the solar cable.' }}

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

