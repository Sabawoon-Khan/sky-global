<?php

namespace App\Support;

class DailyAttendanceMarks
{
    public const PRESENT = 'P';

    public const ABSENT = 'A';

    public const SICK = 'S';

    public const ANNUAL = 'AL';

    public const CASUAL = 'CL';

    public const OTHER = 'O';

    /**
     * @return list<string>
     */
    public static function allowedValues(): array
    {
        return [
            self::PRESENT,
            self::ABSENT,
            self::SICK,
            self::ANNUAL,
            self::CASUAL,
            self::OTHER,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $marks
     * @return array<string, string>
     */
    public static function normalize(?array $marks, int $daysInMonth): array
    {
        $normalized = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $value = strtoupper(trim((string) ($marks[(string) $day] ?? $marks[$day] ?? '')));

            $normalized[(string) $day] = in_array($value, self::allowedValues(), true)
                ? $value
                : '';
        }

        return $normalized;
    }

    /**
     * @param  array<string, string>  $marks
     * @return array{
     *     days_present: int,
     *     days_absent: int,
     *     days_sick_leave: int,
     *     days_annual_leave: int,
     *     days_casual_leave: int,
     *     days_other: int,
     *     days_leave: int,
     * }
     */
    public static function totalsFromMarks(array $marks): array
    {
        $counts = [
            'days_present' => 0,
            'days_absent' => 0,
            'days_sick_leave' => 0,
            'days_annual_leave' => 0,
            'days_casual_leave' => 0,
            'days_other' => 0,
        ];

        foreach ($marks as $mark) {
            match ($mark) {
                self::PRESENT => $counts['days_present']++,
                self::ABSENT => $counts['days_absent']++,
                self::SICK => $counts['days_sick_leave']++,
                self::ANNUAL => $counts['days_annual_leave']++,
                self::CASUAL => $counts['days_casual_leave']++,
                self::OTHER => $counts['days_other']++,
                default => null,
            };
        }

        $counts['days_leave'] = $counts['days_sick_leave']
            + $counts['days_annual_leave']
            + $counts['days_casual_leave']
            + $counts['days_other'];

        return $counts;
    }

    /**
     * @param  array<string, string>  $marks
     */
    public static function hasAnyMarks(array $marks): bool
    {
        return collect($marks)->contains(
            fn (string $mark) => $mark !== '',
        );
    }
}
