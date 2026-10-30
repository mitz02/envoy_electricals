<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt {{ $receipt->ref_id }}</title>
    <style>
        @page {
            size: 1012pt 428pt;
            margin: 0;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            margin: 0;
            padding: 0;
            width: 1012pt;
            height: 428pt;
            position: relative;
            background: #ffffff;
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            color: #072b56;
            overflow: hidden;
        }
        .bg-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 1012pt;
            height: 428pt;
            z-index: 1;
        }
        .receipt-content {
            position: absolute;
            top: 0;
            left: 0;
            width: 1012pt;
            height: 428pt;
            z-index: 2;
        }
        .field {
            position: absolute;
            color: #072b56;
            font-weight: bold;
            white-space: nowrap;
            overflow: hidden;
        }
        .val-date {
            top: 91pt;
            left: 812pt;
            width: 160pt;
            font-size: 13.5pt;
        }
        .val-from-1 {
            top: 137pt;
            left: 172pt;
            width: 790pt;
            font-size: 15pt;
        }
        .val-from-2 {
            top: 170pt;
            left: 22pt;
            width: 940pt;
            font-size: 14pt;
        }
        .val-sum-1 {
            top: 211pt;
            left: 142pt;
            width: 820pt;
            font-size: 13pt;
        }
        .val-sum-2 {
            top: 245pt;
            left: 22pt;
            width: 660pt;
            font-size: 13pt;
        }
        .val-amount {
            top: 244pt;
            left: 746pt;
            width: 188pt;
            font-size: 15pt;
            font-weight: bold;
            text-align: right;
        }
        .val-kobo {
            top: 244pt;
            left: 940pt;
            width: 30pt;
            font-size: 13.5pt;
            font-weight: bold;
            text-align: center;
        }
        .val-for-1 {
            top: 285pt;
            left: 204pt;
            width: 760pt;
            font-size: 14pt;
        }
        .val-for-2 {
            top: 323pt;
            left: 22pt;
            width: 940pt;
            font-size: 14pt;
        }
        .val-sig-customer-wrap {
            position: absolute;
            top: 345pt;
            left: 274pt;
            width: 245pt;
            height: 40pt;
            text-align: center;
        }
        .val-sig-envoy-wrap {
            position: absolute;
            top: 345pt;
            left: 749pt;
            width: 220pt;
            height: 40pt;
            text-align: center;
        }
        .val-sig-img {
            max-width: 220pt;
            max-height: 40pt;
        }
        .val-sig-text {
            font-size: 13.5pt;
            font-style: italic;
            color: #1e3a8a;
            line-height: 40pt;
        }
        .val-sig-envoy {
            top: 363pt;
            left: 779pt;
            width: 185pt;
            font-size: 13pt;
            font-weight: bold;
            color: #072b56;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $bgBase64 = base64_encode(file_get_contents(public_path('images/receipt_template.png')));
        $amountFloat = (float) $receipt->amount;
        $nairaInt = (int) floor($amountFloat);
        $koboInt = (int) round(($amountFloat - $nairaInt) * 100);
        $koboFormatted = sprintf('%02d', $koboInt);
        $amountFormatted = number_format($nairaInt);

        // Split "The sum of" if long
        $sumWords = $receipt->amount_in_words;
        $sumLine1 = $sumWords;
        $sumLine2 = '';
        if (mb_strlen($sumWords) > 65) {
            $words = explode(' ', $sumWords);
            $line1Words = [];
            $len = 0;
            while (!empty($words) && ($len + mb_strlen($words[0]) + 1) <= 65) {
                $w = array_shift($words);
                $line1Words[] = $w;
                $len += mb_strlen($w) + 1;
            }
            $sumLine1 = implode(' ', $line1Words);
            $sumLine2 = implode(' ', $words);
        }

        // Split "Being payment for" if long
        $forText = $receipt->payment_for;
        $forLine1 = $forText;
        $forLine2 = '';
        if (mb_strlen($forText) > 60) {
            $words = explode(' ', $forText);
            $line1Words = [];
            $len = 0;
            while (!empty($words) && ($len + mb_strlen($words[0]) + 1) <= 60) {
                $w = array_shift($words);
                $line1Words[] = $w;
                $len += mb_strlen($w) + 1;
            }
            $forLine1 = implode(' ', $line1Words);
            $forLine2 = implode(' ', $words);
        }
    @endphp

    <img class="bg-img" src="data:image/png;base64,{{ $bgBase64 }}" alt="Envoy Cash Receipt" />

    <div class="receipt-content">
        <div class="field val-date">
            {{ \Carbon\Carbon::parse($receipt->receipt_date)->format('d/m/Y') }}
        </div>

        <div class="field val-from-1">
            {{ $receipt->customer_name }}
        </div>

        <div class="field val-sum-1">
            {{ $sumLine1 }}
        </div>
        @if($sumLine2)
            <div class="field val-sum-2">
                {{ $sumLine2 }}
            </div>
        @endif

        <div class="field val-amount">
            {{ $amountFormatted }}
        </div>
        <div class="field val-kobo">
            {{ $koboFormatted }}
        </div>

        <div class="field val-for-1">
            {{ $forLine1 }}
        </div>
        @if($forLine2)
            <div class="field val-for-2">
                {{ $forLine2 }}
            </div>
        @endif

        @php
            $sig = $receipt->customer_signature ?: '';
            $isImage = str_starts_with($sig, 'data:image');
        @endphp

        <div class="val-sig-customer-wrap">
            @if($isImage)
                <img src="{{ $sig }}" class="val-sig-img" alt="Customer Signature" />
            @elseif($sig)
                <span class="val-sig-text">{{ $sig }}</span>
            @endif
        </div>

        @php
            $envoySig = $receipt->envoy_signature ?: '';
            $isEnvoyImage = str_starts_with($envoySig, 'data:image');
        @endphp

        <div class="val-sig-envoy-wrap">
            @if($isEnvoyImage)
                <img src="{{ $envoySig }}" class="val-sig-img" alt="Envoy Signature" />
            @elseif($envoySig)
                <span class="val-sig-text">{{ $envoySig }}</span>
            @endif
        </div>

        @if(empty($receipt->envoy_signature))
            <div class="field val-sig-envoy">
                {{ $receipt->signatory_title ?: 'Authorized Signatory' }}
            </div>
        @endif
    </div>
</body>
</html>
