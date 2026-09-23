<?php

namespace App\Services;

use App\Models\Finance\GeneralExpense;
use App\Models\Finance\GeneralIncome;
use App\Models\Finance\ProjectExpense;
use App\Models\Finance\ProjectIncome;
use App\Models\Finance\TaxPayment;
use Carbon\CarbonInterface;

class FinanceLedgerService
{
    /**
     * @return array{
     *     period_start: string,
     *     period_end: string,
     *     period_label: string,
     *     sections: list<array{
     *         key: string,
     *         label: string,
     *         lines: list<array<string, mixed>>,
     *         subtotal_afn: float,
     *         subtotal_usd: float
     *     }>,
     *     totals: array{
     *         income_afn: float,
     *         income_usd: float,
     *         tax_afn: float,
     *         expense_afn: float,
     *         expense_usd: float,
     *         net_afn: float
     *     }
     * }
     */
    public function build(CarbonInterface $start, CarbonInterface $end): array
    {
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $incomeLines = $this->mapIncomes($startDate, $endDate);
        $taxLines = $this->mapTaxPayments($startDate, $endDate);
        $expenseLines = $this->mapExpenses($startDate, $endDate);

        $sections = [
            $this->section('income', __('Revenue'), $incomeLines),
            $this->section('tax', __('Tax'), $taxLines),
            $this->section('expense', __('Expenses'), $expenseLines),
        ];

        $incomeAfn = $sections[0]['subtotal_afn'];
        $incomeUsd = $sections[0]['subtotal_usd'];
        $taxAfn = $sections[1]['subtotal_afn'];
        $expenseAfn = $sections[2]['subtotal_afn'];
        $expenseUsd = $sections[2]['subtotal_usd'];

        return [
            'period_start' => $startDate,
            'period_end' => $endDate,
            'period_label' => $startDate.' – '.$endDate,
            'sections' => $sections,
            'totals' => [
                'income_afn' => round($incomeAfn, 2),
                'income_usd' => round($incomeUsd, 2),
                'tax_afn' => round($taxAfn, 2),
                'expense_afn' => round($expenseAfn, 2),
                'expense_usd' => round($expenseUsd, 2),
                'net_afn' => round($incomeAfn - $taxAfn - $expenseAfn, 2),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapIncomes(string $startDate, string $endDate): array
    {
        $general = GeneralIncome::query()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get()
            ->map(fn (GeneralIncome $row) => $this->lineFromRecord(
                'general_income',
                $row->id,
                'income',
                $row->description,
                $row->transaction_date?->toDateString(),
                (float) $row->amount,
                $row->currency,
                $row->amount_usd !== null ? (float) $row->amount_usd : null,
                $row->reference_number,
                $row->category,
            ));

        $project = ProjectIncome::query()
            ->with('project:id,name')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get()
            ->map(fn (ProjectIncome $row) => $this->lineFromRecord(
                'project_income',
                $row->id,
                'income',
                $row->description ?: ($row->project?->name ?? __('Project income')),
                $row->transaction_date?->toDateString(),
                (float) $row->amount,
                $row->currency,
                $row->amount_usd !== null ? (float) $row->amount_usd : null,
                $row->reference_number,
                __('Project income'),
            ));

        return $this->sortLines($general->concat($project));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapExpenses(string $startDate, string $endDate): array
    {
        $general = GeneralExpense::query()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get()
            ->map(fn (GeneralExpense $row) => $this->lineFromRecord(
                'general_expense',
                $row->id,
                'expense',
                $row->description,
                $row->transaction_date?->toDateString(),
                (float) $row->amount,
                $row->currency,
                $row->amount_usd !== null ? (float) $row->amount_usd : null,
                $row->reference_number,
                $row->category,
            ));

        $project = ProjectExpense::query()
            ->with('project:id,name')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get()
            ->map(fn (ProjectExpense $row) => $this->lineFromRecord(
                'project_expense',
                $row->id,
                'expense',
                $row->description ?: ($row->project?->name ?? __('Project expense')),
                $row->transaction_date?->toDateString(),
                (float) $row->amount,
                $row->currency,
                $row->amount_usd !== null ? (float) $row->amount_usd : null,
                $row->reference_number,
                __('Project expense'),
            ));

        return $this->sortLines($general->concat($project));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    private function sortLines($lines): array
    {
        return $lines
            ->sortBy(fn (array $line) => ($line['transaction_date'] ?? '').'-'.($line['source_id'] ?? 0))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapTaxPayments(string $startDate, string $endDate): array
    {
        return TaxPayment::query()
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get()
            ->map(fn (TaxPayment $row) => $this->lineFromRecord(
                'tax_payment',
                $row->id,
                'tax',
                $row->notes ?: __('Tax payment'),
                $row->payment_date?->toDateString(),
                (float) $row->amount,
                $row->currency,
                null,
                $row->reference_number,
                __('Transaction tax'),
            ))
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array{
     *     key: string,
     *     label: string,
     *     lines: list<array<string, mixed>>,
     *     subtotal_afn: float,
     *     subtotal_usd: float
     * }
     */
    private function section(string $key, string $label, array $lines): array
    {
        $numbered = collect($lines)
            ->values()
            ->map(function (array $line, int $index) {
                $line['row_number'] = $index + 1;

                return $line;
            })
            ->all();

        $subtotalAfn = 0.0;
        $subtotalUsd = 0.0;

        foreach ($numbered as $line) {
            $subtotalAfn += (float) ($line['amount_afn'] ?? 0);
            $subtotalUsd += (float) ($line['amount_usd'] ?? 0);
        }

        return [
            'key' => $key,
            'label' => $label,
            'lines' => $numbered,
            'subtotal_afn' => round($subtotalAfn, 2),
            'subtotal_usd' => round($subtotalUsd, 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lineFromRecord(
        string $source,
        int $sourceId,
        string $section,
        ?string $description,
        ?string $date,
        float $amount,
        ?string $currency,
        ?float $amountUsd,
        ?string $reference,
        ?string $category,
    ): array {
        $currency = strtoupper((string) ($currency ?: 'AFN'));
        $amountAfn = $currency === 'AFN' ? $amount : 0.0;
        $amountUsdResolved = $currency === 'USD' ? $amount : ($amountUsd ?? 0.0);

        if ($currency === 'AFN' && $amountUsd !== null && $amountUsd > 0) {
            $amountUsdResolved = $amountUsd;
        }

        return [
            'source' => $source,
            'source_id' => $sourceId,
            'section' => $section,
            'description' => $description ?: '—',
            'category' => $category,
            'transaction_date' => $date,
            'date_label' => $date ?? '',
            'amount_afn' => round($amountAfn, 2),
            'amount_usd' => round($amountUsdResolved, 2),
            'document_ref' => $reference,
            'notes' => null,
        ];
    }
}
