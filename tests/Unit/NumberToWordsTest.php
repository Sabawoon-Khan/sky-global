<?php

namespace Tests\Unit;

use App\Support\NumberToWords;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NumberToWordsTest extends TestCase
{
    #[DataProvider('moneyCases')]
    public function test_money_in_words(float $amount, string $currency, string $expected): void
    {
        $this->assertSame($expected, NumberToWords::money($amount, $currency));
    }

    /** @return array<string, array{0: float, 1: string, 2: string}> */
    public static function moneyCases(): array
    {
        return [
            'invoice sample' => [6530, 'USD', 'SIX THOUSAND FIVE HUNDRED AND THIRTY US DOLLARS'],
            'one dollar' => [1, 'USD', 'ONE US DOLLAR'],
            'afn' => [100, 'AFN', 'ONE HUNDRED AFGHANIS'],
        ];
    }
}
