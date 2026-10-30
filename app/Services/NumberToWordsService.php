<?php

namespace App\Services;

use NumberFormatter;

class NumberToWordsService
{
    /**
     * Convert an amount into Naira and Kobo words.
     * e.g., 250000.00 => "Two Hundred and Fifty Thousand Naira Only"
     * e.g., 250000.50 => "Two Hundred and Fifty Thousand Naira and Fifty Kobo Only"
     */
    public static function toNaira(float|int|string $amount): string
    {
        $amount = (float) $amount;
        $naira = (int) floor($amount);
        $kobo = (int) round(($amount - $naira) * 100);

        $f = new NumberFormatter('en', NumberFormatter::SPELLOUT);

        $nairaWords = '';
        if ($naira === 0) {
            $nairaWords = 'Zero Naira';
        } else {
            $spelled = $f->format($naira);
            $spelled = str_replace('-', ' ', $spelled);
            $nairaWords = ucwords($spelled).' Naira';
        }

        if ($kobo > 0) {
            $koboSpelled = $f->format($kobo);
            $koboSpelled = str_replace('-', ' ', $koboSpelled);

            return $nairaWords.' and '.ucwords($koboSpelled).' Kobo Only';
        }

        return $nairaWords.' Only';
    }
}
