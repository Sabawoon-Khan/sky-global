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

    #[DataProvider('yearlyTaxCases')]
    public function test_yearly_income_tax(float $profit, float $expectedTax): void
    {
        $this->assertSame(
            $expectedTax,
            $this->service->calculateCorporateIncomeTax($profit),
        );
    }

    #[DataProvider('quarterlyTaxCases')]
    public function test_quarterly_income_tax(float $profit, float $expectedTax): void
    {
        $this->assertSame(
            $expectedTax,
            $this->service->calculateTax($profit, AfghanistanCompanyTaxService::QUARTERLY_RATE),
        );
    }

    public function test_summarize_yearly_includes_breakdown(): void
    {
        $summary = $this->service->summarize(1_000_000, 600_000);

        $this->assertSame(0.1, $summary['rate']);
        $this->assertSame(10, $summary['rate_percent']);
        $this->assertSame(1_000_000.0, $summary['income']);
        $this->assertSame(600_000.0, $summary['expenses']);
        $this->assertSame(400_000.0, $summary['taxable_profit']);
        $this->assertSame(40_000.0, $summary['tax_due']);
        $this->assertSame(360_000.0, $summary['net_after_tax']);
    }

    public function test_summarize_quarterly_uses_four_percent(): void
    {
        $summary = $this->service->summarize(
            1_000_000,
            600_000,
            AfghanistanCompanyTaxService::QUARTERLY_RATE,
        );

        $this->assertSame(0.04, $summary['rate']);
        $this->assertSame(4, $summary['rate_percent']);
        $this->assertSame(16_000.0, $summary['tax_due']);
        $this->assertSame(384_000.0, $summary['net_after_tax']);
        $this->assertSame(8_000.0, $summary['their_share_due']);
        $this->assertSame(8_000.0, $summary['company_share_due']);
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
    public static function yearlyTaxCases(): array
    {
        return [
            'zero profit' => [0.0, 0.0],
            'loss' => [-50_000.0, 0.0],
            'small profit' => [10_000.0, 1_000.0],
            'large profit' => [400_000.0, 40_000.0],
        ];
    }

    /**
     * @return array<string, array{0: float, 1: float}>
     */
    public static function quarterlyTaxCases(): array
    {
        return [
            'zero profit' => [0.0, 0.0],
            'loss' => [-50_000.0, 0.0],
            'small profit' => [10_000.0, 400.0],
            'large profit' => [400_000.0, 16_000.0],
        ];
    }
}
