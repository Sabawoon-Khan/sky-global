<?php

namespace App\Services;

/**
 * Company income tax helpers for the finance tax tab.
 *
 * Quarterly tax is 4% of taxable profit. Yearly tax is 10%.
 */
class AfghanistanCompanyTaxService
{
    public const QUARTERLY_RATE = 0.04;

    public const QUARTERLY_THEIR_RATE = 0.02;

    public const QUARTERLY_COMPANY_RATE = 0.02;

    public const YEARLY_RATE = 0.10;

    public const CORPORATE_RATE = self::YEARLY_RATE;

    /**
     * Tax on taxable profit (AFN). Losses produce zero tax.
     */
    public function calculateTax(float $taxableProfit, float $rate): float
    {
        return round(max(0, $taxableProfit) * $rate, 2);
    }

    /**
     * Yearly tax on taxable profit (AFN).
     */
    public function calculateCorporateIncomeTax(float $taxableProfit): float
    {
        return $this->calculateTax($taxableProfit, self::YEARLY_RATE);
    }

    /**
     * @return array{
     *     rate: float,
     *     rate_percent: int,
     *     income: float,
     *     expenses: float,
     *     taxable_profit: float,
     *     tax_due: float,
     *     net_after_tax: float,
     *     their_share_rate: float,
     *     company_share_rate: float,
     *     their_share_due: float,
     *     company_share_due: float
     * }
     */
    public function summarize(float $income, float $expenses, float $rate = self::YEARLY_RATE): array
    {
        $income = round($income, 2);
        $expenses = round($expenses, 2);
        $taxableProfit = round($income - $expenses, 2);
        $taxDue = $this->calculateTax($taxableProfit, $rate);
        $isQuarterly = abs($rate - self::QUARTERLY_RATE) < 0.00001;
        $theirDue = $isQuarterly
            ? $this->calculateTax($taxableProfit, self::QUARTERLY_THEIR_RATE)
            : 0.0;
        $companyDue = $isQuarterly
            ? $this->calculateTax($taxableProfit, self::QUARTERLY_COMPANY_RATE)
            : $taxDue;

        return [
            'rate' => $rate,
            'rate_percent' => (int) round($rate * 100),
            'income' => $income,
            'expenses' => $expenses,
            'taxable_profit' => $taxableProfit,
            'tax_due' => $taxDue,
            'net_after_tax' => round($taxableProfit - $taxDue, 2),
            'their_share_rate' => $isQuarterly ? self::QUARTERLY_THEIR_RATE : 0.0,
            'company_share_rate' => $isQuarterly ? self::QUARTERLY_COMPANY_RATE : $rate,
            'their_share_due' => $theirDue,
            'company_share_due' => $companyDue,
        ];
    }
}
