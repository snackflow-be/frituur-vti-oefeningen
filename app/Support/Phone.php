<?php

namespace App\Support;

/**
 * Belgische gsm-nummers: opgeslagen als 04xxxxxxxx, getoond als 04xx xx xx xx.
 */
final class Phone
{
    /**
     * Strip spaties, punten, streepjes, slashes en haakjes; aanvaardt 04xx…, +32 4xx…, +32 (0)4xx… en 0032 4xx….
     * Geeft `04xxxxxxxx` terug of null als het geen Belgisch mobiel nummer is.
     */
    public static function normalize(string $value): ?string
    {
        // De gangbare schrijfwijze "+32 (0)470 …": de nul tussen haakjes valt weg na de landcode.
        $value = preg_replace('/^\s*(\+32|0032)\s*\(0\)/', '$1', trim($value)) ?? '';

        $clean = preg_replace('/[\s.\/\-()]+/', '', $value) ?? '';

        if (preg_match('/^(?:\+32|0032|0)(4\d{8})$/', $clean, $m) !== 1) {
            return null;
        }

        return '0'.$m[1];
    }

    /**
     * "0470123456" → "0470 12 34 56". Een niet-normaliseerbare waarde komt ongewijzigd terug.
     */
    public static function format(string $value): string
    {
        $normalized = self::normalize($value);

        if ($normalized === null) {
            return $value;
        }

        return substr($normalized, 0, 4).' '.substr($normalized, 4, 2).' '.substr($normalized, 6, 2).' '.substr($normalized, 8, 2);
    }
}
