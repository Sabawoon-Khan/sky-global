<?php

namespace Tests\Unit;

use App\Services\PayrollCalculationService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AfghanistanWageTaxTest extends TestCase
{
    private PayrollCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PayrollCalculationService;
    }

    #[DataProvider('wageTaxCases')]
    public function test_afghanistan_monthly_wage_withholding(float $income, float $expectedTax): void
    {
        $this->assertSame(
            $expectedTax,
            $this->service->calculateAfghanistanWageTax($income),
        );
    }

    /**
     * @return array<string, array{0: float, 1: float}>
     */
    public static function wageTaxCases(): array
    {
        return [
            'exempt at zero' => [0.0, 0.0],
            'exempt at threshold' => [5_000.0, 0.0],
            '2% bracket mid' => [8_750.0, 75.0], // (8750 - 5000) * 0.02
            '2% bracket top' => [12_500.0, 150.0], // (12500 - 5000) * 0.02
            '10% bracket mid' => [50_000.0, 3_900.0], // 150 + (50000 - 12500) * 0.10
            '10% bracket top' => [100_000.0, 8_900.0], // 150 + (100000 - 12500) * 0.10
            '20% bracket' => [150_000.0, 18_900.0], // 8900 + (150000 - 100000) * 0.20
            'negative treated as zero' => [-100.0, 0.0],
        ];
    }
}
