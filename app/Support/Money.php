<?php

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    public static function cents(string|int $value): int
    {
        $value = (string) $value;
        if (! preg_match('/^\d{1,13}(\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException('Nominal tidak valid. Gunakan angka tanpa pemisah ribuan.');
        }
        [$whole,$fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $whole) * 100 + (int) str_pad($fraction, 2, '0');
    }

    public static function decimal(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
