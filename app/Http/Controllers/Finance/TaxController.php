<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Controller;
use App\Models\Finance\GeneralExpense;
use App\Models\Finance\GeneralIncome;
use App\Models\Finance\ProjectExpense;
use App\Models\Finance\ProjectIncome;
use App\Models\Finance\TaxPayment;
use App\Services\AfghanistanCompanyTaxService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TaxController extends Controller
{
    use AuthorizesMisPermissions, StoresOptionalAttachments;

    /** @var list<string> */
    private const REPORT_PERIODS = ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'];

    public function index(Request $request, AfghanistanCompanyTaxService $companyTax): Response
    {
        $this->authorizePermission($request, 'finance.view');

        return Inertia::render('mis/finance/Tax', [
            'tax' => $this->buildCompanyTaxReport($companyTax),
            'payments' => $this->paymentRows(),
        ]);
    }

    public function print(Request $request, AfghanistanCompanyTaxService $companyTax): Response
    {
        $this->authorizePermission($request, 'finance.view');

        $period = $request->string('period')->toString();

        if (! in_array($period, self::REPORT_PERIODS, true)) {
            $period = 'monthly';
        }

        $report = $this->buildCompanyTaxReport($companyTax);
        $rows = $period === 'quarterly' ? $report['quarters'] : $report[$period];
        $isQuarterly = $period === 'quarterly';

        return Inertia::render('mis/finance/TaxPrint', [
            'period' => $period,
            'period_label' => $this->periodLabel($period),
            'rate_percent' => $isQuarterly
                ? $report['quarterly_rate_percent']
                : $report['yearly_rate_percent'],
            'rate_note' => match ($period) {
                'yearly' => '10% of taxable profit',
                'quarterly' => 'Quarterly 4% is split: 2% paid by them, 2% paid by the company.',
                default => 'Estimated at 10% yearly rate',
            },
            'show_split' => $isQuarterly,
            'rows' => $rows,
            'totals' => $this->sumRows($rows),
            'generated_on' => now()->toDayDateTimeString(),
            'current_year' => $report['current_year'],
        ]);
    }

    public function storePayment(Request $request, AfghanistanCompanyTaxService $companyTax): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.create');

        $validated = $request->validate([
            'period_type' => ['required', 'in:quarterly,yearly'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'quarter' => ['nullable', 'integer', 'min:1', 'max:4', 'required_if:period_type,quarterly'],
            'amount' => ['required_if:period_type,yearly', 'nullable', 'numeric', 'min:0'],
            'their_amount' => ['required_if:period_type,quarterly', 'nullable', 'numeric', 'min:0'],
            'company_amount' => ['required_if:period_type,quarterly', 'nullable', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['file', 'max:10240'],
        ]);

        $year = (int) $validated['year'];
        $quarter = $validated['period_type'] === 'quarterly' ? (int) $validated['quarter'] : null;
        [$start, $end] = $this->periodBounds($validated['period_type'], $year, $quarter);
        $rate = $validated['period_type'] === 'quarterly'
            ? AfghanistanCompanyTaxService::QUARTERLY_RATE
            : AfghanistanCompanyTaxService::YEARLY_RATE;
        $summary = $companyTax->summarize(
            $this->sumIncomeInRange($start, $end),
            $this->sumExpenseInRange($start, $end),
            $rate,
        );

        if ($validated['period_type'] === 'quarterly') {
            $theirAmount = round((float) ($validated['their_amount'] ?? 0), 2);
            $companyAmount = round((float) ($validated['company_amount'] ?? 0), 2);
            $amount = round($theirAmount + $companyAmount, 2);
        } else {
            $theirAmount = 0.0;
            $companyAmount = round((float) ($validated['amount'] ?? 0), 2);
            $amount = $companyAmount;
        }

        if ($amount < 0.01) {
            throw ValidationException::withMessages([
                'amount' => 'Enter the amount paid by them and by the company.',
            ]);
        }

        DB::transaction(function () use ($request, $validated, $year, $quarter, $start, $end, $rate, $summary, $amount, $theirAmount, $companyAmount): void {
            $payment = TaxPayment::query()->create([
                'period_type' => $validated['period_type'],
                'year' => $year,
                'quarter' => $quarter,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'rate' => $rate,
                'rate_percent' => (int) round($rate * 100),
                'tax_due' => $summary['tax_due'],
                'amount' => $amount,
                'their_amount' => $theirAmount,
                'company_amount' => $companyAmount,
                'currency' => 'AFN',
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'paid',
                'created_by' => $request->user()->id,
            ]);

            $stored = $this->storeUploadedAttachments($request, $payment, 'documents');

            if ($stored < 1) {
                throw ValidationException::withMessages([
                    'documents' => 'Documents are required to record a tax payment.',
                ]);
            }
        });

        $this->notifyMisCreated(
            'finance',
            __('Tax payment'),
            route('finance.tax', [], false),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tax payment recorded.',
        ]);

        return back();
    }

    /**
     * @return array{
     *     current_year: int,
     *     current_quarter: int,
     *     quarterly_rate_percent: int,
     *     yearly_rate_percent: int,
     *     paid_this_year: float,
     *     year: array<string, float|int>,
     *     quarters: list<array<string, float|int|string>>,
     *     years: list<array<string, float|int|string>>,
     *     daily: list<array<string, float|int|string>>,
     *     weekly: list<array<string, float|int|string>>,
     *     monthly: list<array<string, float|int|string>>,
     *     yearly: list<array<string, float|int|string>>
     * }
     */
    private function buildCompanyTaxReport(AfghanistanCompanyTaxService $companyTax): array
    {
        $currentYear = (int) now()->year;
        $currentQuarter = (int) ceil(now()->month / 3);

        $yearStart = now()->copy()->startOfYear();
        $yearEnd = now()->copy()->endOfYear();
        $yearSummary = $this->withPaymentProgress(
            $companyTax->summarize(
                $this->sumIncomeInRange($yearStart, $yearEnd),
                $this->sumExpenseInRange($yearStart, $yearEnd),
                AfghanistanCompanyTaxService::YEARLY_RATE,
            ),
            'yearly',
            $currentYear,
            null,
        );

        $quarters = [];
        for ($quarter = 1; $quarter <= 4; $quarter++) {
            [$start, $end] = $this->periodBounds('quarterly', $currentYear, $quarter);
            $summary = $companyTax->summarize(
                $this->sumIncomeInRange($start, $end),
                $this->sumExpenseInRange($start, $end),
                AfghanistanCompanyTaxService::QUARTERLY_RATE,
            );

            $quarters[] = [
                'quarter' => $quarter,
                'label' => "Q{$quarter} {$currentYear}",
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                ...$this->withPaymentProgress($summary, 'quarterly', $currentYear, $quarter),
            ];
        }

        $years = [];
        for ($offset = 2; $offset >= 0; $offset--) {
            $year = $currentYear - $offset;
            [$start, $end] = $this->periodBounds('yearly', $year, null);
            $summary = $companyTax->summarize(
                $this->sumIncomeInRange($start, $end),
                $this->sumExpenseInRange($start, $end),
                AfghanistanCompanyTaxService::YEARLY_RATE,
            );

            $years[] = [
                'year' => $year,
                'label' => (string) $year,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                ...$this->withPaymentProgress($summary, 'yearly', $year, null),
            ];
        }

        $daily = collect(range(13, 0))->map(function (int $i) use ($companyTax) {
            $day = now()->subDays($i);
            $start = $day->copy()->startOfDay();
            $end = $day->copy()->endOfDay();
            $summary = $companyTax->summarize(
                $this->sumIncomeInRange($start, $end),
                $this->sumExpenseInRange($start, $end),
                AfghanistanCompanyTaxService::YEARLY_RATE,
            );

            return [
                'label' => $start->format('D, M j'),
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                ...$summary,
            ];
        })->values()->all();

        $weekly = collect(range(7, 0))->map(function (int $i) use ($companyTax) {
            $week = now()->copy()->startOfWeek(Carbon::MONDAY)->subWeeks($i);
            $start = $week->copy()->startOfDay();
            $end = $week->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
            $summary = $companyTax->summarize(
                $this->sumIncomeInRange($start, $end),
                $this->sumExpenseInRange($start, $end),
                AfghanistanCompanyTaxService::YEARLY_RATE,
            );

            return [
                'label' => $start->format('M j').' – '.$end->format('M j, Y'),
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                ...$summary,
            ];
        })->values()->all();

        $monthly = collect(range(11, 0))->map(function (int $i) use ($companyTax) {
            $month = now()->copy()->startOfMonth()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();
            $summary = $companyTax->summarize(
                $this->sumIncomeInRange($start, $end),
                $this->sumExpenseInRange($start, $end),
                AfghanistanCompanyTaxService::YEARLY_RATE,
            );

            return [
                'label' => $start->format('F Y'),
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                ...$summary,
            ];
        })->values()->all();

        return [
            'current_year' => $currentYear,
            'current_quarter' => $currentQuarter,
            'quarterly_rate_percent' => (int) round(AfghanistanCompanyTaxService::QUARTERLY_RATE * 100),
            'quarterly_their_rate_percent' => (int) round(AfghanistanCompanyTaxService::QUARTERLY_THEIR_RATE * 100),
            'quarterly_company_rate_percent' => (int) round(AfghanistanCompanyTaxService::QUARTERLY_COMPANY_RATE * 100),
            'yearly_rate_percent' => (int) round(AfghanistanCompanyTaxService::YEARLY_RATE * 100),
            'paid_this_year' => (float) TaxPayment::query()->where('year', $currentYear)->sum('amount'),
            'year' => $yearSummary,
            'quarters' => $quarters,
            'years' => $years,
            'daily' => $daily,
            'weekly' => $weekly,
            'monthly' => $monthly,
            'yearly' => $years,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function paymentRows(): array
    {
        return TaxPayment::query()
            ->with('attachments')
            ->latest('payment_date')
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (TaxPayment $payment) => [
                'id' => $payment->id,
                'period_type' => $payment->period_type,
                'year' => $payment->year,
                'quarter' => $payment->quarter,
                'label' => $payment->period_type === 'quarterly'
                    ? "Q{$payment->quarter} {$payment->year}"
                    : (string) $payment->year,
                'rate_percent' => $payment->rate_percent,
                'tax_due' => (float) $payment->tax_due,
                'amount' => (float) $payment->amount,
                'their_amount' => (float) $payment->their_amount,
                'company_amount' => (float) $payment->company_amount,
                'payment_date' => $payment->payment_date?->toDateString(),
                'payment_method' => $payment->payment_method,
                'reference_number' => $payment->reference_number,
                'notes' => $payment->notes,
                'attachments' => $payment->attachments,
            ])
            ->all();
    }

    /**
     * @param  array<string, float|int>  $summary
     * @return array<string, float|int>
     */
    private function withPaymentProgress(array $summary, string $periodType, int $year, ?int $quarter): array
    {
        $totals = TaxPayment::query()
            ->where('period_type', $periodType)
            ->where('year', $year)
            ->when(
                $quarter !== null,
                fn ($query) => $query->where('quarter', $quarter),
                fn ($query) => $query->whereNull('quarter'),
            )
            ->selectRaw('COALESCE(SUM(amount), 0) as paid, COALESCE(SUM(their_amount), 0) as their_paid, COALESCE(SUM(company_amount), 0) as company_paid')
            ->first();

        $paid = (float) ($totals?->paid ?? 0);
        $theirPaid = (float) ($totals?->their_paid ?? 0);
        $companyPaid = (float) ($totals?->company_paid ?? 0);
        $due = (float) $summary['tax_due'];
        $theirDue = (float) ($summary['their_share_due'] ?? 0);
        $companyDue = (float) ($summary['company_share_due'] ?? $due);

        return [
            ...$summary,
            'paid' => round($paid, 2),
            'remaining' => round(max(0, $due - $paid), 2),
            'their_paid' => round($theirPaid, 2),
            'company_paid' => round($companyPaid, 2),
            'their_remaining' => round(max(0, $theirDue - $theirPaid), 2),
            'company_remaining' => round(max(0, $companyDue - $companyPaid), 2),
        ];
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function periodBounds(string $periodType, int $year, ?int $quarter): array
    {
        if ($periodType === 'quarterly') {
            $start = Carbon::create($year, (($quarter ?? 1) - 1) * 3 + 1, 1)->startOfDay();

            return [$start, $start->copy()->addMonths(2)->endOfMonth()];
        }

        $start = Carbon::create($year, 1, 1)->startOfDay();

        return [$start, Carbon::create($year, 12, 31)->endOfDay()];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{income: float, expenses: float, taxable_profit: float, tax_due: float, net_after_tax: float}
     */
    private function sumRows(array $rows): array
    {
        $income = 0.0;
        $expenses = 0.0;
        $taxDue = 0.0;

        foreach ($rows as $row) {
            $income += (float) ($row['income'] ?? 0);
            $expenses += (float) ($row['expenses'] ?? 0);
            $taxDue += (float) ($row['tax_due'] ?? 0);
        }

        $taxableProfit = round($income - $expenses, 2);

        return [
            'income' => round($income, 2),
            'expenses' => round($expenses, 2),
            'taxable_profit' => $taxableProfit,
            'tax_due' => round($taxDue, 2),
            'net_after_tax' => round($taxableProfit - $taxDue, 2),
        ];
    }

    private function periodLabel(string $period): string
    {
        return match ($period) {
            'daily' => 'Daily tax',
            'weekly' => 'Weekly tax',
            'monthly' => 'Monthly tax',
            'quarterly' => 'Quarterly tax',
            default => 'Yearly tax',
        };
    }

    private function sumIncomeInRange(CarbonInterface $start, CarbonInterface $end): float
    {
        return $this->sumInRange(ProjectIncome::class, $start, $end)
            + $this->sumInRange(GeneralIncome::class, $start, $end);
    }

    private function sumExpenseInRange(CarbonInterface $start, CarbonInterface $end): float
    {
        return $this->sumInRange(ProjectExpense::class, $start, $end)
            + $this->sumInRange(GeneralExpense::class, $start, $end);
    }

    /** @param class-string $modelClass */
    private function sumInRange(string $modelClass, CarbonInterface $start, CarbonInterface $end): float
    {
        return (float) $modelClass::query()
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');
    }
}
