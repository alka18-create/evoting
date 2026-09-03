<?php

namespace App\Support;

/**
 * P2-04: netralkan formula injection saat export ke CSV/Excel.
 * Sel yang diawali = + - @ (atau tab/CR) bisa dieksekusi sebagai formula
 * saat dibuka di spreadsheet. Prefix dengan "'" (didukung Excel/LibreOffice
 * sebagai penanda teks) agar tetap tampil sama bagi pengguna.
 */
class ExcelSanitizer
{
    public static function cell(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        if ($value === '') {
            return $value;
        }

        $first = $value[0];

        if (in_array($first, ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * @param  array<int,mixed>  $row
     * @return array<int,mixed>
     */
    public static function row(array $row): array
    {
        return array_map([self::class, 'cell'], $row);
    }
}
