<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Concerns\AppliesListFilters;
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
use App\Services\DocumentNumberService;
use App\Support\CompanyDocument;
use App\Support\NumberToWords;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    use AppliesListFilters, AuthorizesMisPermissions, StoresOptionalAttachments;

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

    public function invoices(Request $request): Response
    {
        $this->authorizePermission($request, 'finance.view');

        $filters = $this->listFilters($request, ['draft', 'sent', 'paid', 'overdue', 'cancelled']);

        $query = Invoice::query()
            ->with(['project:id,code,name', 'organization:id,name', 'attachments']);
        $this->applyListFilters($query, $filters, [
            'date_column' => 'issue_date',
            'search_columns' => ['invoice_number', 'notes', 'services'],
            'search_relations' => [
                'project' => ['code', 'name'],
                'organization' => ['name'],
            ],
        ]);

        $invoices = (clone $query)
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
            'filters' => $filters,
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
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'services' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['nullable', 'string', 'in:draft,sent,paid,overdue,cancelled'],
            'line_items' => ['nullable', 'array'],
            'line_items.*.description' => ['nullable', 'string', 'max:255'],
            'line_items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.days' => ['nullable', 'integer', 'min:1'],
        ]);

        $lineItems = $this->normalizedInvoiceLines($validated['line_items'] ?? []);
        unset($validated['line_items']);

        $totals = $this->totalsFromLines(
            $lineItems,
            (float) ($validated['subtotal'] ?? 0),
            (float) ($validated['tax'] ?? 0),
            (float) ($validated['total'] ?? 0),
        );

        $invoice = DB::transaction(function () use ($request, $validated, $lineItems, $totals) {
            $invoice = Invoice::query()->create([
                ...$validated,
                'invoice_number' => DocumentNumberService::nextInvoiceNumber(),
                'subtotal' => $totals['subtotal'],
                'tax' => $totals['tax'],
                'total' => $totals['total'],
                'currency' => strtoupper($validated['currency'] ?? 'USD'),
                'status' => $validated['status'] ?? 'draft',
                'created_by' => $request->user()->id,
            ]);

            foreach ($lineItems as $item) {
                $invoice->lineItems()->create($item);
            }

            return $invoice;
        });

        $this->storeOptionalAttachment($request, $invoice);

        $this->notifyMisCreated(
            'finance',
            $invoice->invoice_number ?: __('Invoice'),
            route('finance.invoices.print', $invoice, false),
        );

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
            'issue_date' => ['sometimes', 'date'],
            'due_date' => ['nullable', 'date'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date'],
            'services' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'subtotal' => ['sometimes', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'total' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['nullable', 'string', 'in:draft,sent,paid,overdue,cancelled'],
            'line_items' => ['nullable', 'array'],
            'line_items.*.description' => ['nullable', 'string', 'max:255'],
            'line_items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.days' => ['nullable', 'integer', 'min:1'],
        ]);

        $lineItems = array_key_exists('line_items', $validated)
            ? $this->normalizedInvoiceLines($validated['line_items'] ?? [])
            : null;
        unset($validated['line_items']);

        if (isset($validated['currency'])) {
            $validated['currency'] = strtoupper($validated['currency']);
        }

        if (is_array($lineItems)) {
            $totals = $this->totalsFromLines(
                $lineItems,
                (float) ($validated['subtotal'] ?? $invoice->subtotal),
                (float) ($validated['tax'] ?? $invoice->tax),
                (float) ($validated['total'] ?? $invoice->total),
            );
            $validated['subtotal'] = $totals['subtotal'];
            $validated['tax'] = $totals['tax'];
            $validated['total'] = $totals['total'];
        }

        $invoice->update($validated);

        if (is_array($lineItems)) {
            $invoice->lineItems()->delete();
            foreach ($lineItems as $item) {
                $invoice->lineItems()->create($item);
            }
        }

        $invoiceLabel = $invoice->invoice_number ?: __('Invoice');
        $invoiceUrl = route('finance.invoices.print', $invoice, false);

        if (isset($validated['status']) && $invoice->wasChanged('status')) {
            $this->notifyMisStatus('finance', $invoiceLabel, $invoice->status, $invoiceUrl);
        } else {
            $this->notifyMisUpdated('finance', $invoiceLabel, $invoiceUrl);
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

        $label = $invoice->invoice_number ?: __('Invoice');
        $invoice->delete();

        $this->notifyMisDeleted('finance', $label, route('finance.invoices', [], false));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Invoice deleted.',
        ]);

        return back();
    }

    public function print(Request $request, Invoice $invoice): Response
    {
        $this->authorizePermission($request, 'finance.view');

        $invoice->load(['organization', 'project', 'lineItems']);

        return Inertia::render('mis/finance/InvoicePrint', [
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'issue_date' => $invoice->issue_date?->format('d-M-Y'),
                'due_date' => $invoice->due_date?->format('d-M-Y'),
                'period_start' => $invoice->period_start?->format('d-M-Y'),
                'period_end' => $invoice->period_end?->format('d-M-Y'),
                'services' => $invoice->services,
                'notes' => $invoice->notes,
                'subtotal' => (float) $invoice->subtotal,
                'tax' => (float) $invoice->tax,
                'total' => (float) $invoice->total,
                'currency' => $invoice->currency ?: 'USD',
                'status' => $invoice->status,
                'organization' => $invoice->organization ? [
                    'name' => $invoice->organization->name,
                    'address' => $invoice->organization->address,
                    'email' => $invoice->organization->email,
                    'phone' => $invoice->organization->phone,
                ] : null,
                'project' => $invoice->project?->only(['id', 'code', 'name']),
                'line_items' => $invoice->lineItems->map(fn ($item) => [
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'days' => (int) ($item->days ?: 1),
                    'total' => (float) $item->total,
                ])->values()->all(),
            ],
            'company' => CompanyDocument::profile(),
            'amount_in_words' => NumberToWords::money((float) $invoice->total, $invoice->currency ?: 'USD'),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return list<array{description: string, quantity: float, unit_price: float, days: int, total: float}>
     */
    private function normalizedInvoiceLines(array $lines): array
    {
        $items = [];

        foreach ($lines as $line) {
            $description = trim((string) ($line['description'] ?? ''));

            if ($description === '') {
                continue;
            }

            $quantity = (float) ($line['quantity'] ?? 1);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $days = max(1, (int) ($line['days'] ?? 1));

            $items[] = [
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'days' => $days,
                'total' => round($quantity * $unitPrice * $days, 2),
            ];
        }

        return $items;
    }

    /**
     * @param  list<array{total: float}>  $lineItems
     * @return array{subtotal: float, tax: float, total: float}
     */
    private function totalsFromLines(array $lineItems, float $subtotal, float $tax, float $total): array
    {
        if ($lineItems !== []) {
            $subtotal = round(array_sum(array_column($lineItems, 'total')), 2);
            $total = round($subtotal + $tax, 2);
        }

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total > 0 ? $total : round($subtotal + $tax, 2),
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
