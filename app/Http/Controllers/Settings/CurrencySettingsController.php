<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreCurrencyRequest;
use App\Http\Requests\Settings\StoreExchangeRateRequest;
use App\Http\Requests\Settings\UpdateCurrencyRequest;
use App\Http\Requests\Settings\UpdateExchangeRateRequest;
use App\Models\Finance\Currency;
use App\Models\Finance\ExchangeRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CurrencySettingsController extends Controller
{
    use AuthorizesMisPermissions;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'settings.edit');

        return Inertia::render('settings/Finance/Currencies', [
            'currencies' => Currency::query()
                ->orderByDesc('is_default')
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'symbol', 'is_active', 'is_default']),
            'exchangeRates' => ExchangeRate::query()
                ->orderByDesc('effective_date')
                ->orderBy('from_currency')
                ->get(['id', 'from_currency', 'to_currency', 'rate', 'effective_date'])
                ->map(fn (ExchangeRate $rate) => [
                    'id' => $rate->id,
                    'from_currency' => $rate->from_currency,
                    'to_currency' => $rate->to_currency,
                    'rate' => (float) $rate->rate,
                    'effective_date' => $rate->effective_date?->toDateString(),
                ])
                ->values(),
        ]);
    }

    public function storeCurrency(StoreCurrencyRequest $request): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.edit');

        $validated = $request->validated();
        $payload = [
            'code' => strtoupper((string) $validated['code']),
            'name' => $validated['name'],
            'symbol' => $validated['symbol'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'is_default' => (bool) ($validated['is_default'] ?? false),
        ];

        if ($payload['is_default']) {
            Currency::query()->update(['is_default' => false]);
            $payload['is_active'] = true;
        }

        Currency::query()->create($payload);

        return back()->with('success', 'Currency created.');
    }

    public function updateCurrency(UpdateCurrencyRequest $request, Currency $currency): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.edit');

        $validated = $request->validated();
        $payload = [];

        if (array_key_exists('code', $validated)) {
            $payload['code'] = strtoupper((string) $validated['code']);
        }

        if (array_key_exists('name', $validated)) {
            $payload['name'] = $validated['name'];
        }

        if (array_key_exists('symbol', $validated)) {
            $payload['symbol'] = $validated['symbol'];
        }

        if (array_key_exists('is_active', $validated)) {
            if ($currency->is_default && ! $validated['is_active']) {
                return back()->withErrors(['currency' => 'Default currency cannot be deactivated.']);
            }

            $payload['is_active'] = (bool) $validated['is_active'];
        }

        if (array_key_exists('is_default', $validated)) {
            $makeDefault = (bool) $validated['is_default'];
            $payload['is_default'] = $makeDefault;

            if ($makeDefault) {
                Currency::query()->whereKeyNot($currency->id)->update(['is_default' => false]);
                $payload['is_active'] = true;
            }
        }

        $currency->update($payload);

        return back()->with('success', 'Currency updated.');
    }

    public function destroyCurrency(Request $request, Currency $currency): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.edit');

        if ($currency->is_default) {
            return back()->withErrors(['currency' => 'Default currency cannot be deleted.']);
        }

        if ($this->currencyIsInUse($currency->code)) {
            return back()->withErrors(['currency' => 'Currency is already used in finance records and cannot be deleted.']);
        }

        $currency->delete();

        return back()->with('success', 'Currency deleted.');
    }

    public function storeExchangeRate(StoreExchangeRateRequest $request): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.edit');

        $validated = $request->validated();

        ExchangeRate::query()->create([
            'from_currency' => strtoupper((string) $validated['from_currency']),
            'to_currency' => strtoupper((string) $validated['to_currency']),
            'rate' => $validated['rate'],
            'effective_date' => $validated['effective_date'],
        ]);

        return back()->with('success', 'Exchange rate created.');
    }

    public function updateExchangeRate(UpdateExchangeRateRequest $request, ExchangeRate $exchangeRate): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.edit');

        $validated = $request->validated();
        $payload = [];

        if (array_key_exists('from_currency', $validated)) {
            $payload['from_currency'] = strtoupper((string) $validated['from_currency']);
        }

        if (array_key_exists('to_currency', $validated)) {
            $payload['to_currency'] = strtoupper((string) $validated['to_currency']);
        }

        if (array_key_exists('rate', $validated)) {
            $payload['rate'] = $validated['rate'];
        }

        if (array_key_exists('effective_date', $validated)) {
            $payload['effective_date'] = $validated['effective_date'];
        }

        $exchangeRate->update($payload);

        return back()->with('success', 'Exchange rate updated.');
    }

    public function destroyExchangeRate(Request $request, ExchangeRate $exchangeRate): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.edit');

        $exchangeRate->delete();

        return back()->with('success', 'Exchange rate deleted.');
    }

    private function currencyIsInUse(string $code): bool
    {
        return DB::table('project_incomes')->where('currency', $code)->exists()
            || DB::table('project_expenses')->where('currency', $code)->exists()
            || DB::table('general_incomes')->where('currency', $code)->exists()
            || DB::table('general_expenses')->where('currency', $code)->exists()
            || DB::table('payments')->where('currency', $code)->exists()
            || DB::table('project_budgets')->where('currency', $code)->exists()
            || DB::table('invoices')->where('currency', $code)->exists()
            || DB::table('exchange_rates')->where('from_currency', $code)->orWhere('to_currency', $code)->exists();
    }
}
