<?php

namespace App\Helpers;

class Currency
{
    public static function format(float|int|string $value, int $decimals = 0): string
    {
        $n = (float) $value;
        if (is_nan($n)) {
            return '₦0';
        }

        return '₦'.number_format($n, $decimals, '.', ',');
    }
}
