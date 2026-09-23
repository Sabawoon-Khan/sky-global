<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Finance\GeneralExpense;
use App\Models\Finance\GeneralIncome;
use App\Services\FinanceLedgerService;
use App\Support\AfghanSolarDate;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceLedgerController extends Controller
{
    use AuthorizesMisPermissions;

    public function index(Request $request, FinanceLedgerService $ledger): Response
    {
        $this->authorizePermission($request, 'finance.view');

        [$start, $end] = $this->resolvePeriod($request);
        $report = $ledger->build($start, $end);

        return Inertia::render('mis/finance/Ledger/Index', [
            'ledger' => $report,
            'filters' => [
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
            ],
            'system' => $this->systemDateContext(),
        ]);
    }

    public function print(Request $request, FinanceLedgerService $ledger): Response
    {
        $this->authorizePermission($request, 'finance.view');

        [$start, $end] = $this->resolvePeriod($request);
        $report = $ledger->build($start, $end);

        return Inertia::render('mis/finance/Ledger/Print', [
            'ledger' => $report,
        ]);
    }

    public function storeLine(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'finance.create');

        $validated = $request->validate([
            'section' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:100'],
            'transaction_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'amount_usd' => ['nullable', 'numeric', 'min:0'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date'],
        ]);

        $currency = strtoupper($validated['currency'] ?? 'AFN');
        $payload = [
            'amount' => $validated['amount'],
            'currency' => $currency,
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? null,
            'transaction_date' => $validated['transaction_date'],
            'reference_number' => $validated['reference_number'] ?? null,
            'payment_method' => 'cash',
            'status' => 'recorded',
            'created_by' => $request->user()->id,
        ];

        if ($currency === 'AFN') {
            $payload['amount_usd'] = $validated['amount_usd'] ?? null;
        } else {
            $payload['amount_usd'] = $validated['amount'];
        }

        if ($validated['section'] === 'income') {
            GeneralIncome::query()->create($payload);
        } else {
            GeneralExpense::query()->create($payload);
        }

        return redirect()->route('finance.ledger', [
            'period_start' => $validated['period_start'] ?? $validated['transaction_date'],
            'period_end' => $validated['period_end'] ?? $validated['transaction_date'],
        ])->with('success', __('Ledger line recorded.'));
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod(Request $request): array
    {
        $startRaw = $request->string('period_start')->toString();
        $endRaw = $request->string('period_end')->toString();

        if ($startRaw !== '' && $endRaw !== '') {
            return [
                Carbon::parse($startRaw)->startOfDay(),
                Carbon::parse($endRaw)->endOfDay(),
            ];
        }

        return $this->currentSystemMonthBounds();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function currentSystemMonthBounds(): array
    {
        [$year, $month] = AfghanSolarDate::parts();

        return [
            AfghanSolarDate::monthStart($year, $month),
            AfghanSolarDate::monthEnd($year, $month),
        ];
    }

    /**
     * @return array{
     *     today: string,
     *     period_start: string,
     *     period_end: string
     * }
     */
    private function systemDateContext(): array
    {
        [$start, $end] = $this->currentSystemMonthBounds();

        return [
            'today' => AfghanSolarDate::now()->toDateString(),
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
        ];
    }
}
