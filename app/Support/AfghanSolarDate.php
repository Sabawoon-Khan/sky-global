<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Afghan Hijri Shamsi (solar) calendar helpers.
 *
 * Civil conversion matches the Jalali algorithm used for Afghanistan's official
 * solar year, which starts on 1 Hamal (~21 March).
 */
class AfghanSolarDate
{
    public const TIMEZONE = 'Asia/Kabul';

    /** @var list<string> */
    public const MONTHS = [
        'Hamal',
        'Sawr',
        'Jawza',
        'Saratan',
        'Asad',
        'Sunbula',
        'Mizan',
        'Aqrab',
        'Qaws',
        'Jadi',
        'Dalwa',
        'Hoot',
    ];

    public static function now(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
    }

    /**
     * @return array{0: int, 1: int, 2: int} Shamsi year, month, day
     */
    public static function parts(CarbonInterface|\DateTimeInterface|string|null $date = null): array
    {
        $local = Carbon::parse($date ?? self::now())->timezone(self::TIMEZONE);

        return self::gregorianToShamsi(
            (int) $local->year,
            (int) $local->month,
            (int) $local->day,
        );
    }

    public static function currentYear(CarbonInterface|\DateTimeInterface|string|null $date = null): int
    {
        return self::parts($date)[0];
    }

    public static function currentQuarter(CarbonInterface|\DateTimeInterface|string|null $date = null): int
    {
        return (int) ceil(self::parts($date)[1] / 3);
    }

    public static function monthName(int $month): string
    {
        $index = max(1, min(12, $month)) - 1;

        return __(self::MONTHS[$index]);
    }

    public static function format(CarbonInterface|\DateTimeInterface|string|null $date, bool $withYear = true): string
    {
        if ($date === null) {
            return '';
        }

        [$year, $month, $day] = self::parts($date);
        $label = $day.' '.self::monthName($month);

        return $withYear ? $label.' '.$year : $label;
    }

    public static function formatMonth(int $year, int $month): string
    {
        return self::monthName($month).' '.$year;
    }

    public static function formatQuarter(int $year, int $quarter): string
    {
        $startMonth = (($quarter - 1) * 3) + 1;

        return "Q{$quarter} {$year} · ".self::monthName($startMonth).'–'.self::monthName($startMonth + 2);
    }

    public static function monthStart(int $year, int $month): Carbon
    {
        return self::toGregorian($year, $month, 1);
    }

    public static function monthEnd(int $year, int $month): Carbon
    {
        if ($month >= 12) {
            return self::monthStart($year + 1, 1)->subDay()->endOfDay();
        }

        return self::monthStart($year, $month + 1)->subDay()->endOfDay();
    }

    public static function yearStart(int $year): Carbon
    {
        return self::monthStart($year, 1);
    }

    public static function yearEnd(int $year): Carbon
    {
        return self::monthEnd($year, 12);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function quarterBounds(int $year, int $quarter): array
    {
        $startMonth = (($quarter - 1) * 3) + 1;

        return [
            self::monthStart($year, $startMonth),
            self::monthEnd($year, $startMonth + 2),
        ];
    }

    /**
     * @return array{0: int, 1: int} Shamsi year and month after adding months
     */
    public static function addMonths(int $year, int $month, int $delta): array
    {
        $index = (($year * 12) + ($month - 1)) + $delta;
        $year = intdiv($index, 12);
        $month = $index - ($year * 12) + 1;

        if ($month < 1) {
            $year--;
            $month += 12;
        }

        return [$year, $month];
    }

    public static function toGregorian(int $year, int $month, int $day): Carbon
    {
        [$gregorianYear, $gregorianMonth, $gregorianDay] = self::shamsiToGregorian($year, $month, $day);

        return Carbon::create($gregorianYear, $gregorianMonth, $gregorianDay, 0, 0, 0, self::TIMEZONE)->startOfDay();
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    public static function gregorianToShamsi(int $gy, int $gm, int $gd): array
    {
        $gDayOfYear = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400) + $gd + $gDayOfYear[$gm - 1];
        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return [$jy, $jm, $jd];
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    public static function shamsiToGregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;
        $days = -355668 + (365 * $jy) + (intdiv($jy, 33) * 8) + intdiv(($jy % 33) + 3, 4)
            + $jd + ($jm < 7 ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
        $gy = 400 * intdiv($days, 146097);
        $days %= 146097;

        if ($days > 36524) {
            $gy += 100 * intdiv(--$days, 36524);
            $days %= 36524;

            if ($days >= 365) {
                $days++;
            }
        }

        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $gd = $days + 1;
        $leap = (($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0)) ? 29 : 28;
        $monthDays = [0, 31, $leap, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        for ($gm = 1; $gm <= 12 && $gd > $monthDays[$gm]; $gm++) {
            $gd -= $monthDays[$gm];
        }

        return [$gy, $gm, $gd];
    }
}
