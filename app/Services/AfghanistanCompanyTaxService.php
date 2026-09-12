<?php

namespace App\Services;

/**
 * Afghanistan company (legal person) income tax helpers.
 *
 * Under the Afghanistan Income Tax Law, corporations and limited liability
 * companies are generally taxed at a flat 20% on taxable profit.
 */
class AfghanistanCompanyTaxService
{
    public const CORPORATE_RATE = 0.20;

    /**
     * Corporate income tax on taxable profit (AFN).
     * Losses produce zero tax (no negative liability).
     */
    public function calculateCorporateIncomeTax(float $taxableProfit): float
    {
        return round(max(0, $taxableProfit) * self::CORPORATE_RATE, 2);
    }

    /**
     * @return array{
     *     rate: float,
     *     rate_percent: int,
     *     income: float,
     *     expenses: float,
     *     taxable_profit: float,
     *     tax_due: float,
     *     net_after_tax: float
     * }
     */
    public function summarize(float $income, float $expenses): array
    {
        $income = round($income, 2);
        $expenses = round($expenses, 2);
        $taxableProfit = round($income - $expenses, 2);
        $taxDue = $this->calculateCorporateIncomeTax($taxableProfit);

        return [
            'rate' => self::CORPORATE_RATE,
            'rate_percent' => (int) round(self::CORPORATE_RATE * 100),
            'income' => $income,
            'expenses' => $expenses,
            'taxable_profit' => $taxableProfit,
            'tax_due' => $taxDue,
            'net_after_tax' => round($taxableProfit - $taxDue, 2),
        ];
    }
}
