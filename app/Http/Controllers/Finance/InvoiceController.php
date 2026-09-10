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
                'currency' => $invoice->currency,
                'issue_date' => $invoice->issue_date?->toDateString(),
                'due_date' => $invoice->due_date?->toDateString(),
                'project' => $invoice->project?->only(['id', 'code', 'name']),
                'organization' => $invoice->organization?->only(['id', 'name']),
                'attachments' => $invoice->attachments,
            ]);

        $totalIncome = ProjectIncome::query()->sum('amount_usd') ?: ProjectIncome::query()->sum('amount');
        $totalGeneralIncome = GeneralIncome::query()->sum('amount_usd') ?: GeneralIncome::query()->sum('amount');
        $totalExpenses = ProjectExpense::query()->sum('amount_usd') ?: ProjectExpense::query()->sum('amount');
        $totalGeneral = GeneralExpense::query()->sum('amount_usd') ?: GeneralExpense::query()->sum('amount');
        $totalInvoices = Invoice::query()->sum('total');
        $currencyBreakdown = $this->buildCurrencyBreakdown();

        return Inertia::render('mis/finance/Index', [
            'summary' => [
                'total_income' => (float) $totalIncome + (float) $totalGeneralIncome,
                'project_income' => (float) $totalIncome,
                'general_income' => (float) $totalGeneralIncome,
                'total_expenses' => (float) $totalExpenses + (float) $totalGeneral,
                'project_expenses' => (float) $totalExpenses,
                'general_expenses' => (float) $totalGeneral,
                'total_invoices' => (float) $totalInvoices,
                'outstanding' => (float) Invoice::query()->whereIn('status', ['draft', 'sent', 'overdue'])->sum('total'),
                'currency_breakdown' => $currencyBreakdown->values()->all(),
            ],
            'incomes' => ProjectIncome::query()->with(['project', 'attachments'])->latest('transaction_date')->limit(50)->get(),
            'expenses' => ProjectExpense::query()->with(['project', 'attachments'])->latest('transaction_date')->limit(50)->get(),
            'generalIncomes' => GeneralIncome::query()->with('attachments')->latest('transaction_date')->limit(50)->get(),
            'generalExpenses' => GeneralExpense::query()->with('attachments')->latest('transaction_date')->limit(50)->get(),
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
            'currency' => $validated['currency'] ?? 'AFN',
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

        $invoice->update($validated);

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
            ->selectRaw("UPPER(COALESCE(currency, 'USD')) as currency, SUM(total) as total")
            ->groupByRaw("UPPER(COALESCE(currency, 'USD'))")
            ->pluck('total', 'currency');
        $outstandingByCurrency = Invoice::query()
            ->whereIn('status', ['draft', 'sent', 'overdue'])
            ->selectRaw("UPPER(COALESCE(currency, 'USD')) as currency, SUM(total) as total")
            ->groupByRaw("UPPER(COALESCE(currency, 'USD'))")
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
                ->selectRaw("UPPER(COALESCE(currency, 'USD')) as currency, SUM($amountColumn) as total")
                ->groupByRaw("UPPER(COALESCE(currency, 'USD'))")
                ->pluck('total', 'currency');

            foreach ($rows as $currency => $total) {
                $totals[$currency] = (float) ($totals->get($currency, 0.0)) + (float) $total;
            }
        }

        return $totals;
    }
}
