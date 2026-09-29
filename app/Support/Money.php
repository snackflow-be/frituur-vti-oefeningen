<?php

namespace App\Support;

/**
 * Bedragen zijn altijd centen (integer). Hier enkel tonen en parsen.
 */
final class Money
{
    /**
     * 930 → "€ 9,30".
     */
    public static function format(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return sprintf('%s€ %d,%02d', $sign, intdiv($cents, 100), $cents % 100);
    }

    /**
     * "3,50" → 350, "3.50" → 350, "€ 3" → 300, "3,5" → 350. Ongeldig of negatief → null.
     */
    public static function parseEuro(string $value): ?int
    {
        $clean = trim(str_replace(['€', ' ', "\u{a0}"], '', $value));

        if ($clean === '' || preg_match('/^(\d{1,6})(?:[,.](\d{1,2}))?$/', $clean, $m) !== 1) {
            return null;
        }

        $euros = (int) $m[1];
        $cents = isset($m[2]) ? (int) str_pad($m[2], 2, '0') : 0;

        return $euros * 100 + $cents;
    }
}
