<?php

namespace App\Support;

final class InrMoney
{
    public static function format(int $paise): string
    {
        $rupees = intdiv($paise, 100);
        $minor = $paise % 100;
        $formatted = '₹'.number_format($rupees);

        return $minor === 0 ? $formatted : $formatted.'.'.str_pad((string) $minor, 2, '0', STR_PAD_LEFT);
    }

    public static function inputValue(int $paise): string
    {
        return intdiv($paise, 100).'.'.str_pad((string) ($paise % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function toPaise(string $rupees): int
    {
        [$whole, $fraction] = array_pad(explode('.', $rupees, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
