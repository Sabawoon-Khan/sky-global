<?php

namespace Tests\Unit;

use App\Services\AfghanistanCompanyTaxService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AfghanistanCompanyTaxTest extends TestCase
{
    private AfghanistanCompanyTaxService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AfghanistanCompanyTaxService;
    }

    #[DataProvider('corporateTaxCases')]
    public function test_corporate_income_tax(float $profit, float $expectedTax): void
    {
        $this->assertSame(
            $expectedTax,
            $this->service->calculateCorporateIncomeTax($profit),
        );
    }

    public function test_summarize_includes_breakdown(): void
    {
        $summary = $this->service->summarize(1_000_000, 600_000);

        $this->assertSame(0.2, $summary['rate']);
        $this->assertSame(20, $summary['rate_percent']);
        $this->assertSame(1_000_000.0, $summary['income']);
        $this->assertSame(600_000.0, $summary['expenses']);
        $this->assertSame(400_000.0, $summary['taxable_profit']);
        $this->assertSame(80_000.0, $summary['tax_due']);
        $this->assertSame(320_000.0, $summary['net_after_tax']);
    }

    public function test_loss_produces_zero_tax(): void
    {
        $summary = $this->service->summarize(100_000, 250_000);

        $this->assertSame(-150_000.0, $summary['taxable_profit']);
        $this->assertSame(0.0, $summary['tax_due']);
        $this->assertSame(-150_000.0, $summary['net_after_tax']);
    }

    /**
     * @return array<string, array{0: float, 1: float}>
     */
    public static function corporateTaxCases(): array
    {
        return [
            'zero profit' => [0.0, 0.0],
            'loss' => [-50_000.0, 0.0],
            'small profit' => [10_000.0, 2_000.0],
            'large profit' => [400_000.0, 80_000.0],
        ];
    }
}
