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
            'exempt at threshold' => [10_000.0, 0.0],
            '10% bracket mid' => [50_000.0, 4_000.0], // (50000 - 10000) * 0.10
            '10% bracket top' => [100_000.0, 9_000.0], // (100000 - 10000) * 0.10
            '20% bracket' => [150_000.0, 19_000.0], // 9000 + (150000 - 100000) * 0.20
            'negative treated as zero' => [-100.0, 0.0],
        ];
    }
}
