<?php

namespace App\Support;

class NumberToWords
{
    public static function money(float|int|string $amount, string $currency = 'USD'): string
    {
        $int = (int) round((float) $amount);
        $words = strtoupper(self::integer($int));
        $code = strtoupper($currency);

        $suffix = match ($code) {
            'AFN' => $int === 1 ? 'AFGHANI' : 'AFGHANIS',
            default => $int === 1 ? 'US DOLLAR' : 'US DOLLARS',
        };

        return $words.' '.$suffix;
    }

    public static function integer(int $number): string
    {
        if ($number === 0) {
            return 'zero';
        }

        if ($number < 0) {
            return 'minus '.self::integer(abs($number));
        }

        $words = '';

        $billions = intdiv($number, 1_000_000_000);
        $number %= 1_000_000_000;
        $millions = intdiv($number, 1_000_000);
        $number %= 1_000_000;
        $thousands = intdiv($number, 1_000);
        $remainder = $number % 1_000;

        if ($billions > 0) {
            $words .= self::chunk($billions).' billion';
        }

        if ($millions > 0) {
            $words .= ($words !== '' ? ' ' : '').self::chunk($millions).' million';
        }

        if ($thousands > 0) {
            $words .= ($words !== '' ? ' ' : '').self::chunk($thousands).' thousand';
        }

        if ($remainder > 0) {
            $words .= ($words !== '' ? ($remainder < 100 ? ' and ' : ' ') : '').self::chunk($remainder);
        }

        return $words;
    }

    private static function chunk(int $number): string
    {
        $ones = [
            1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
            6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten',
            11 => 'eleven', 12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen',
            15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen',
            19 => 'nineteen',
        ];
        $tens = [
            2 => 'twenty', 3 => 'thirty', 4 => 'forty', 5 => 'fifty',
            6 => 'sixty', 7 => 'seventy', 8 => 'eighty', 9 => 'ninety',
        ];

        if ($number >= 100) {
            $hundreds = intdiv($number, 100);
            $rest = $number % 100;
            $chunk = $ones[$hundreds].' hundred';

            if ($rest > 0) {
                $chunk .= ' and '.self::chunk($rest);
            }

            return $chunk;
        }

        if ($number < 20) {
            return $ones[$number];
        }

        $ten = intdiv($number, 10);
        $one = $number % 10;

        return $tens[$ten].($one > 0 ? '-'.$ones[$one] : '');
    }
}
