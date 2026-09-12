<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Controller;
use App\Models\Finance\Currency;
use App\Models\Finance\GeneralExpense;
use App\Models\Finance\GeneralIncome;
use App\Models\Finance\Invoice;
use App\Models\Finance\ProjectExpense;
use App\Models\Finance\ProjectIncome;
use App\Models\Organization;
use App\Models\Project\Project;
use App\Services\AfghanistanCompanyTaxService;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    use AuthorizesMisPermissions, StoresOptionalAttachments;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'finance.view');

        $totalIncome = (float) ProjectIncome::query()->sum('amount');
        $totalGeneralIncome = (float) GeneralIncome::query()->sum('amount');
        $totalExpenses = (float) ProjectExpense::query()->sum('amount');
        $totalGeneral = (float) GeneralExpense::query()->sum('amount');
        $combinedIncome = $totalIncome + $totalGeneralIncome;
        $combinedExpenses = $totalExpenses + $totalGeneral;
        $totalInvoices = Invoice::query()->sum('total');
        $currencyBreakdown = $this->buildCurrencyBreakdown();

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $expensesToday = $this->sumExpenseInRange($todayStart, $todayEnd);
        $expensesThisWeek = $this->sumExpenseInRange($weekStart, $weekEnd);
        $expensesThisMonth = $this->sumExpenseInRange($monthStart, $monthEnd);
        $incomeToday = $this->sumIncomeInRange($todayStart, $todayEnd);
        $incomeThisMonth = $this->sumIncomeInRange($monthStart, $monthEnd);

        return Inertia::render('mis/finance/Index', [
            'summary' => [
                'total_income' => $combinedIncome,
                'project_income' => $totalIncome,
                'general_income' => $totalGeneralIncome,
                'total_expenses' => $combinedExpenses,
                'project_expenses' => $totalExpenses,
                'general_expenses' => $totalGeneral,
                'total_invoices' => (float) $totalInvoices,
                'outstanding' => (float) Invoice::query()->whereIn('status', ['draft', 'sent', 'overdue'])->sum('total'),
                'net' => $combinedIncome - $combinedExpenses,
                'expenses_today' => $expensesToday,
                'expenses_this_week' => $expensesThisWeek,
                'expenses_this_month' => $expensesThisMonth,
                'income_today' => $incomeToday,
                'income_this_month' => $incomeThisMonth,
                'currency_breakdown' => $currencyBreakdown->values()->all(),
            ],
            'charts' => $this->buildOverviewCharts($totalIncome, $totalGeneralIncome, $totalExpenses, $totalGeneral),
        ]);
    }

    public function tax(Request $request, AfghanistanCompanyTaxService $companyTax): Response
    {
        $this->authorizePermission($request, 'finance.view');

        return Inertia::render('mis/finance/Tax', [
            'tax' => $this->buildCompanyTaxReport($companyTax),
        ]);
    }

    public function invoices(Request $request): Response
    {
        $this->authorizePermission($request, 'finance.view');

        $invoices = Invoice::query()
            ->with(['project:id,code,name', 'organization:id,name', 'attachments'])
            ->latest('issue_date')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'total' => (float) $invoice->total,
                'subtotal' => (float) $invoice->subtotal,
                'tax' => (float) $invoice->tax,
                'currency' => $invoice->currency ?: 'AFN',
                'issue_date' => $invoice->issue_date?->toDateString(),
                'due_date' => $invoice->due_date?->toDateString(),
                'project' => $invoice->project?->only(['id', 'code', 'name']),
                'organization' => $invoice->organization?->only(['id', 'name']),
                'attachments' => $invoice->attachments,
            ]);

        return Inertia::render('mis/finance/Invoices/Index', [
            'invoices' => $invoices,
            'projects' => Project::query()
                ->where('is_archived', false)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'organizations' => Organization::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.create');

        if (blank($request->input('project_id'))) {
            $request->merge(['project_id' => null]);
        }

        if (blank($request->input('organization_id'))) {
            $request->merge(['organization_id' => null]);
        }

        if (blank($request->input('tax'))) {
            $request->merge(['tax' => 0]);
        }

        $validated = $request->validate([
            'project_id' => ['nullable', 'exists:projects,id'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'invoice_number' => ['required', 'string', 'max:100', 'unique:invoices,invoice_number'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['nullable', 'string', 'in:draft,sent,paid,overdue,cancelled'],
            'line_items' => ['nullable', 'array'],
            'line_items.*.description' => ['required_with:line_items', 'string', 'max:255'],
            'line_items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.total' => ['nullable', 'numeric', 'min:0'],
        ]);

        $lineItems = $validated['line_items'] ?? [];
        unset($validated['line_items']);

        $invoice = Invoice::query()->create([
            ...$validated,
            'tax' => $validated['tax'] ?? 0,
            'currency' => 'AFN',
            'status' => $validated['status'] ?? 'draft',
            'created_by' => $request->user()->id,
        ]);

        foreach ($lineItems as $item) {
            $invoice->lineItems()->create($item);
        }
        $this->storeOptionalAttachment($request, $invoice);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Invoice created.',
        ]);

        return back();
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.edit');

        $validated = $request->validate([
            'project_id' => ['nullable', 'exists:projects,id'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'invoice_number' => ['sometimes', 'required', 'string', 'max:100', 'unique:invoices,invoice_number,'.$invoice->id],
            'issue_date' => ['sometimes', 'date'],
            'due_date' => ['nullable', 'date'],
            'subtotal' => ['sometimes', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'total' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['nullable', 'string', 'in:draft,sent,paid,overdue,cancelled'],
            'line_items' => ['nullable', 'array'],
            'line_items.*.description' => ['required_with:line_items', 'string', 'max:255'],
            'line_items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.total' => ['nullable', 'numeric', 'min:0'],
        ]);

        $lineItems = $validated['line_items'] ?? null;
        unset($validated['line_items']);

        $invoice->update([
            ...$validated,
            'currency' => 'AFN',
        ]);

        if (is_array($lineItems)) {
            $invoice->lineItems()->delete();
            foreach ($lineItems as $item) {
                $invoice->lineItems()->create($item);
            }
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Invoice updated.',
        ]);

        return back();
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.delete');

        $invoice->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Invoice deleted.',
        ]);

        return back();
    }

    /**
     * @return array{
     *     current_year: int,
     *     rate_percent: int,
     *     year: array<string, float|int>,
     *     quarters: list<array<string, float|int|string>>,
     *     years: list<array<string, float|int|string>>
     * }
     */
    private function buildCompanyTaxReport(AfghanistanCompanyTaxService $companyTax): array
    {
        $currentYear = (int) now()->year;

        $yearStart = now()->copy()->startOfYear();
        $yearEnd = now()->copy()->endOfYear();
        $yearSummary = $companyTax->summarize(
            $this->sumIncomeInRange($yearStart, $yearEnd),
            $this->sumExpenseInRange($yearStart, $yearEnd),
        );

        $quarters = [];
        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $start = now()->copy()->setDate($currentYear, ($quarter - 1) * 3 + 1, 1)->startOfDay();
            $end = $start->copy()->addMonths(2)->endOfMonth();
            $summary = $companyTax->summarize(
                $this->sumIncomeInRange($start, $end),
                $this->sumExpenseInRange($start, $end),
            );

            $quarters[] = [
                'quarter' => $quarter,
                'label' => "Q{$quarter} {$currentYear}",
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                ...$summary,
            ];
        }

        $years = [];
        for ($offset = 2; $offset >= 0; $offset--) {
            $year = $currentYear - $offset;
            $start = now()->copy()->setDate($year, 1, 1)->startOfDay();
            $end = now()->copy()->setDate($year, 12, 31)->endOfDay();
            $summary = $companyTax->summarize(
                $this->sumIncomeInRange($start, $end),
                $this->sumExpenseInRange($start, $end),
            );

            $years[] = [
                'year' => $year,
                'label' => (string) $year,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                ...$summary,
            ];
        }

        return [
            'current_year' => $currentYear,
            'rate_percent' => (int) round(AfghanistanCompanyTaxService::CORPORATE_RATE * 100),
            'year' => $yearSummary,
            'quarters' => $quarters,
            'years' => $years,
        ];
    }

    /**
     * @return array{
     *     daily: list<array{label: string, income: float, expense: float}>,
     *     monthly: list<array{label: string, income: float, expense: float, net: float}>,
     *     finance_breakdown: list<array{key: string, value: float}>,
     *     expense_by_category: list<array{category: string, value: float}>
     * }
     */
    private function buildOverviewCharts(
        float $projectIncome,
        float $generalIncome,
        float $projectExpense,
        float $overhead,
    ): array {
        $daily = collect(range(13, 0))->map(function (int $i) {
            $day = now()->subDays($i);
            $start = $day->copy()->startOfDay();
            $end = $day->copy()->endOfDay();
            $income = $this->sumIncomeInRange($start, $end);
            $expense = $this->sumExpenseInRange($start, $end);

            return [
                'label' => $start->format('M j'),
                'income' => $income,
                'expense' => $expense,
            ];
        })->values()->all();

        $monthly = collect(range(5, 0))->map(function (int $i) {
            $month = now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();
            $income = $this->sumIncomeInRange($start, $end);
            $expense = $this->sumExpenseInRange($start, $end);

            return [
                'label' => $start->format('M Y'),
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
            ];
        })->values()->all();

        $expenseByCategory = GeneralExpense::query()
            ->selectRaw("COALESCE(category, 'other') as category, sum(amount) as total")
            ->groupBy('category')
            ->pluck('total', 'category')
            ->map(fn ($total, $category) => [
                'category' => (string) $category,
                'value' => (float) $total,
            ])
            ->values()
            ->all();

        return [
            'daily' => $daily,
            'monthly' => $monthly,
            'finance_breakdown' => [
                ['key' => 'project_income', 'value' => $projectIncome],
                ['key' => 'general_income', 'value' => $generalIncome],
                ['key' => 'project_expense', 'value' => $projectExpense],
                ['key' => 'overhead', 'value' => $overhead],
            ],
            'expense_by_category' => $expenseByCategory,
        ];
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

    /** @return Collection<int, array<string, float|string>> */
    private function buildCurrencyBreakdown(): Collection
    {
        $currencies = Currency::query()
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->get(['code']);

        $incomeByCurrency = $this->sumByCurrency([ProjectIncome::class, GeneralIncome::class], 'amount');
        $expenseByCurrency = $this->sumByCurrency([ProjectExpense::class, GeneralExpense::class], 'amount');
        $invoiceByCurrency = Invoice::query()
            ->selectRaw("UPPER(COALESCE(currency, 'AFN')) as currency, SUM(total) as total")
            ->groupByRaw("UPPER(COALESCE(currency, 'AFN'))")
            ->pluck('total', 'currency');
        $outstandingByCurrency = Invoice::query()
            ->whereIn('status', ['draft', 'sent', 'overdue'])
            ->selectRaw("UPPER(COALESCE(currency, 'AFN')) as currency, SUM(total) as total")
            ->groupByRaw("UPPER(COALESCE(currency, 'AFN'))")
            ->pluck('total', 'currency');

        $allCurrencies = $currencies->pluck('code')
            ->map(fn (string $code) => strtoupper($code))
            ->merge($incomeByCurrency->keys())
            ->merge($expenseByCurrency->keys())
            ->merge($invoiceByCurrency->keys())
            ->merge($outstandingByCurrency->keys())
            ->unique()
            ->sort()
            ->values();

        return $allCurrencies->map(function (string $currency) use ($incomeByCurrency, $expenseByCurrency, $invoiceByCurrency, $outstandingByCurrency) {
            $income = (float) ($incomeByCurrency->get($currency, 0.0));
            $expenses = (float) ($expenseByCurrency->get($currency, 0.0));
            $invoices = (float) ($invoiceByCurrency->get($currency, 0.0));
            $outstanding = (float) ($outstandingByCurrency->get($currency, 0.0));

            return [
                'currency' => $currency,
                'income' => $income,
                'expenses' => $expenses,
                'invoices' => $invoices,
                'outstanding' => $outstanding,
                'net' => $income - $expenses,
            ];
        });
    }

    /** @param array<int, class-string> $modelClasses */
    private function sumByCurrency(array $modelClasses, string $amountColumn): Collection
    {
        $totals = collect();

        foreach ($modelClasses as $modelClass) {
            $rows = $modelClass::query()
                ->selectRaw("UPPER(COALESCE(currency, 'AFN')) as currency, SUM($amountColumn) as total")
                ->groupByRaw("UPPER(COALESCE(currency, 'AFN'))")
                ->pluck('total', 'currency');

            foreach ($rows as $currency => $total) {
                $totals[$currency] = (float) ($totals->get($currency, 0.0)) + (float) $total;
            }
        }

        return $totals;
    }
}
