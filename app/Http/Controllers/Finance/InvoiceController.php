<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Controller;
use App\Models\Finance\GeneralExpense;
use App\Models\Finance\GeneralIncome;
use App\Models\Finance\Invoice;
use App\Models\Finance\ProjectExpense;
use App\Models\Finance\ProjectIncome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    use AuthorizesMisPermissions, StoresOptionalAttachments;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'finance.view');

        $invoices = Invoice::query()
            ->with(['project', 'organization'])
            ->latest('issue_date')
            ->paginate(20);

        $totalIncome = ProjectIncome::query()->sum('amount_usd') ?: ProjectIncome::query()->sum('amount');
        $totalGeneralIncome = GeneralIncome::query()->sum('amount_usd') ?: GeneralIncome::query()->sum('amount');
        $totalExpenses = ProjectExpense::query()->sum('amount_usd') ?: ProjectExpense::query()->sum('amount');
        $totalGeneral = GeneralExpense::query()->sum('amount_usd') ?: GeneralExpense::query()->sum('amount');
        $totalInvoices = Invoice::query()->sum('total');

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
            ],
            'incomes' => ProjectIncome::query()->with('project')->latest('transaction_date')->limit(50)->get(),
            'expenses' => ProjectExpense::query()->with('project')->latest('transaction_date')->limit(50)->get(),
            'generalIncomes' => GeneralIncome::query()->latest('transaction_date')->limit(50)->get(),
            'generalExpenses' => GeneralExpense::query()->latest('transaction_date')->limit(50)->get(),
            'invoices' => $invoices,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.create');

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
            'created_by' => $request->user()->id,
        ]);

        foreach ($lineItems as $item) {
            $invoice->lineItems()->create($item);
        }
        $this->storeOptionalAttachment($request, $invoice);

        return back()->with('success', 'Invoice created.');
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

        return back()->with('success', 'Invoice updated.');
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.delete');

        $invoice->delete();

        return back()->with('success', 'Invoice deleted.');
    }
}
