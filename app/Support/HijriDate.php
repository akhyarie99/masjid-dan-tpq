<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Konversi tabular Masehi ke Hijriah (algoritma Kuwaiti), akurasi ±1 hari
 * dibanding rukyat. Cukup untuk deteksi bulan Ramadhan pada mode Ramadhan.
 */
class HijriDate
{
    public static function fromGregorian(Carbon $date): array
    {
        $jd = self::gregorianToJulianDay($date->year, $date->month, $date->day);

        return self::julianDayToHijri($jd);
    }

    public static function isRamadhan(Carbon $date): bool
    {
        return self::fromGregorian($date)['month'] === 9;
    }

    private static function gregorianToJulianDay(int $year, int $month, int $day): int
    {
        $a = intdiv(14 - $month, 12);
        $y = $year + 4800 - $a;
        $m = $month + 12 * $a - 3;

        return $day + intdiv(153 * $m + 2, 5) + 365 * $y + intdiv($y, 4) - intdiv($y, 100) + intdiv($y, 400) - 32045;
    }

    private static function julianDayToHijri(int $jd): array
    {
        $islamicEpoch = 1948440;
        $l = $jd - $islamicEpoch + 10632;
        $n = intdiv($l - 1, 10631);
        $l = $l - 10631 * $n + 354;
        $j = intdiv(10985 - $l, 5316) * intdiv(50 * $l, 17719) + intdiv($l, 5670) * intdiv(43 * $l, 15238);
        $l = $l - intdiv(30 - $j, 15) * intdiv(17719 * $j, 50) - intdiv($j, 16) * intdiv(15238 * $j, 43) + 29;
        $month = intdiv(24 * $l, 709);
        $day = $l - intdiv(709 * $month, 24);
        $year = 30 * $n + $j - 30;

        return ['year' => $year, 'month' => $month, 'day' => $day];
    }
}
