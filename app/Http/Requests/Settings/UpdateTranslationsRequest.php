<?php

namespace App\Http\Requests\Settings;

use App\Services\TranslationFileService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTranslationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', Rule::in(array_keys(config('locale.available', [])))],
            'translations' => ['required', 'array'],
            'translations.*' => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $sourceKeys = app(TranslationFileService::class)->sourceKeys();
            $unknownKeys = array_diff(array_keys($this->input('translations', [])), $sourceKeys);

            if ($unknownKeys !== []) {
                $validator->errors()->add('translations', 'One or more translation keys are invalid.');
            }
        });
    }
}
