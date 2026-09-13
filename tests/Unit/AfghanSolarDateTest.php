<?php

namespace Tests\Unit;

use App\Support\AfghanSolarDate;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AfghanSolarDateTest extends TestCase
{
    public function test_nawroz_1405_is_first_hamal(): void
    {
        $this->assertSame(
            [1405, 1, 1],
            AfghanSolarDate::gregorianToShamsi(2026, 3, 21),
        );
        $this->assertSame(
            [2026, 3, 21],
            AfghanSolarDate::shamsiToGregorian(1405, 1, 1),
        );
    }

    public function test_thirteenth_september_2026_is_sunbula(): void
    {
        $this->assertSame(
            [1405, 6, 22],
            AfghanSolarDate::gregorianToShamsi(2026, 9, 13),
        );
    }

    public function test_round_trip_conversion(): void
    {
        foreach ([[2025, 3, 21], [2026, 9, 13], [2027, 1, 1], [2027, 3, 20]] as [$y, $m, $d]) {
            [$sy, $sm, $sd] = AfghanSolarDate::gregorianToShamsi($y, $m, $d);
            $this->assertSame(
                [$y, $m, $d],
                AfghanSolarDate::shamsiToGregorian($sy, $sm, $sd),
            );
        }
    }

    public function test_shamsi_year_and_quarter_bounds(): void
    {
        $this->assertSame('2026-03-21', AfghanSolarDate::yearStart(1405)->toDateString());
        $this->assertSame('2027-03-20', AfghanSolarDate::yearEnd(1405)->toDateString());

        [$q2Start, $q2End] = AfghanSolarDate::quarterBounds(1405, 2);

        $this->assertSame('2026-06-22', $q2Start->toDateString());
        $this->assertSame('2026-09-22', $q2End->toDateString());
    }

    public function test_current_year_uses_kabul_shamsi_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-13 10:00:00', 'UTC'));

        $this->assertSame(1405, AfghanSolarDate::currentYear());
        $this->assertSame(2, AfghanSolarDate::currentQuarter());
    }
}
