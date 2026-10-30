<?php

namespace App\Services;

use App\Models\Receipt;
use Carbon\Carbon;

class ReceiptImageService
{
    /**
     * Generate an exact PNG image of the filled receipt.
     */
    public static function generatePng(Receipt $receipt): string
    {
        $templatePath = public_path('images/receipt_template.png');
        $im = imagecreatefrompng($templatePath);
        imagealphablending($im, true);
        imagesavealpha($im, true);

        // Deep navy blue color: #072b56
        $darkBlue = imagecolorallocate($im, 7, 43, 86);
        $sigBlue = imagecolorallocate($im, 30, 58, 138);
        $white = imagecolorallocate($im, 255, 255, 255);

        // Font selection: DejaVuSans-Bold from vendor
        $fontBold = base64_decode('') ?: '';
        $vendorFontBold = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf');
        $vendorFontRegular = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');

        $font = file_exists($vendorFontBold) ? $vendorFontBold : null;
        $fontReg = file_exists($vendorFontRegular) ? $vendorFontRegular : $font;

        $amountFloat = (float) $receipt->amount;
        $nairaInt = (int) floor($amountFloat);
        $koboInt = (int) round(($amountFloat - $nairaInt) * 100);
        $koboFormatted = sprintf('%02d', $koboInt);
        $amountFormatted = number_format($nairaInt);
        $dateFormatted = Carbon::parse($receipt->receipt_date)->format('d/m/Y');

        if ($font) {
            // Date (line at y = 113, starts after 'Date:' at x=801)
            imagettftext($im, 12, 0, 812, 107, $darkBlue, $font, $dateFormatted);

            // Received From (line at y = 156)
            imagettftext($im, 14, 0, 174, 150, $darkBlue, $font, $receipt->customer_name);

            // The Sum of (line at y = 230)
            $sumWords = $receipt->amount_in_words;
            if (mb_strlen($sumWords) > 65) {
                $words = explode(' ', $sumWords);
                $l1 = [];
                $len = 0;
                while (! empty($words) && ($len + mb_strlen($words[0]) + 1) <= 65) {
                    $w = array_shift($words);
                    $l1[] = $w;
                    $len += mb_strlen($w) + 1;
                }
                imagettftext($im, 12, 0, 144, 223, $darkBlue, $font, implode(' ', $l1));
                imagettftext($im, 12, 0, 24, 257, $darkBlue, $font, implode(' ', $words));
            } else {
                imagettftext($im, 12, 0, 144, 223, $darkBlue, $font, $sumWords);
            }

            // Amount between N. (ends at x=740) and K (starts at x=975)
            $bbox = imagettfbbox(13, 0, $font, $amountFormatted);
            $textWidth = abs($bbox[4] - $bbox[0]);
            $amountX = max(749, 934 - $textWidth);
            imagettftext($im, 13, 0, $amountX, 258, $darkBlue, $font, $amountFormatted);
            imagettftext($im, 12, 0, 942, 258, $darkBlue, $font, $koboFormatted);

            // Being payment for (line at y = 305)
            $forText = $receipt->payment_for;
            if (mb_strlen($forText) > 60) {
                $words = explode(' ', $forText);
                $l1 = [];
                $len = 0;
                while (! empty($words) && ($len + mb_strlen($words[0]) + 1) <= 60) {
                    $w = array_shift($words);
                    $l1[] = $w;
                    $len += mb_strlen($w) + 1;
                }
                imagettftext($im, 13, 0, 209, 298, $darkBlue, $font, implode(' ', $l1));
                imagettftext($im, 13, 0, 24, 335, $darkBlue, $font, implode(' ', $words));
            } else {
                imagettftext($im, 13, 0, 209, 298, $darkBlue, $font, $forText);
            }

            // Signatures (line at y = 384)
            $sig = $receipt->customer_signature ?: '';
            if (str_starts_with($sig, 'data:image')) {
                // It's a base64 data URI - decode and overlay
                $comma = strpos($sig, ',');
                $sigRaw = base64_decode(substr($sig, $comma + 1));
                $sigIm = @imagecreatefromstring($sigRaw);
                if ($sigIm) {
                    // Signature zone: x=289 to 519, y=345 to 385 (40px tall, 230px wide)
                    $sw = imagesx($sigIm);
                    $sh = imagesy($sigIm);
                    imagealphablending($im, true);
                    imagecopyresampled($im, $sigIm, 289, 345, 0, 0, 230, 40, $sw, $sh);
                    imagedestroy($sigIm);
                }
            } elseif ($sig) {
                imagettftext($im, 13, 0, 284, 383, $sigBlue, $fontReg ?: $font, $sig);
            }

            // Envoy Signature (line at y = 384)
            $envoySig = $receipt->envoy_signature ?: '';
            if (str_starts_with($envoySig, 'data:image')) {
                $comma = strpos($envoySig, ',');
                $envoyRaw = base64_decode(substr($envoySig, $comma + 1));
                $envoyIm = @imagecreatefromstring($envoyRaw);
                if ($envoyIm) {
                    $ew = imagesx($envoyIm);
                    $eh = imagesy($envoyIm);
                    imagealphablending($im, true);
                    imagecopyresampled($im, $envoyIm, 749, 345, 0, 0, 220, 40, $ew, $eh);
                    imagedestroy($envoyIm);
                }
            } elseif ($envoySig) {
                imagettftext($im, 13, 0, 764, 383, $sigBlue, $fontReg ?: $font, $envoySig);
            } else {
                $envoyTitle = $receipt->signatory_title ?: 'Authorized Signatory';
                imagettftext($im, 11, 0, 779, 381, $darkBlue, $font, $envoyTitle);
            }
        } else {
            // Fallback to internal GD font if ttf missing
            imagestring($im, 5, 812, 95, $dateFormatted, $darkBlue);
            imagestring($im, 5, 174, 137, $receipt->customer_name, $darkBlue);
            imagestring($im, 4, 144, 213, substr($receipt->amount_in_words, 0, 70), $darkBlue);
            imagestring($im, 5, 754, 247, $amountFormatted, $darkBlue);
            imagestring($im, 5, 942, 247, $koboFormatted, $darkBlue);
            imagestring($im, 4, 209, 287, substr($receipt->payment_for, 0, 70), $darkBlue);
            imagestring($im, 4, 779, 367, 'Envoy Electricals', $darkBlue);
        }

        ob_start();
        imagepng($im, null, 9);
        $data = ob_get_clean();
        imagedestroy($im);

        return $data;
    }
}
