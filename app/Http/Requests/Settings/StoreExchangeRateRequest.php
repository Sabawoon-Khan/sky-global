<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExchangeRateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $updates = [];

        if ($this->has('from_currency')) {
            $updates['from_currency'] = strtoupper((string) $this->input('from_currency'));
        }

        if ($this->has('to_currency')) {
            $updates['to_currency'] = strtoupper((string) $this->input('to_currency'));
        }

        if ($updates !== []) {
            $this->merge($updates);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from_currency' => ['required', 'string', 'size:3', Rule::exists('currencies', 'code')],
            'to_currency' => ['required', 'string', 'size:3', Rule::exists('currencies', 'code'), 'different:from_currency'],
            'rate' => ['required', 'numeric', 'gt:0'],
            'effective_date' => [
                'required',
                'date',
                Rule::unique('exchange_rates')
                    ->where(fn ($query) => $query
                        ->where('from_currency', strtoupper((string) $this->input('from_currency')))
                        ->where('to_currency', strtoupper((string) $this->input('to_currency')))),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'effective_date.unique' => 'An exchange rate already exists for this currency pair and date.',
        ];
    }
}
