<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateTranslationsRequest;
use App\Services\TranslationFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TranslationController extends Controller
{
    use AuthorizesMisPermissions;

    public function __construct(
        private TranslationFileService $translations,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'settings.edit');

        $locale = $request->string('locale')->toString();
        $availableLocales = array_keys(config('locale.available', []));

        if (! in_array($locale, $availableLocales, true)) {
            $locale = config('app.locale', 'en');
        }

        $search = $request->string('search')->trim()->toString();

        return Inertia::render('settings/Translations/Index', [
            'entries' => $this->translations->paginate(
                $locale,
                $search ?: null,
                max(1, $request->integer('page', 1)),
                50,
            ),
            'editingLocale' => $locale,
            'filters' => [
                'search' => $search ?: null,
                'locale' => $locale,
            ],
        ]);
    }

    public function update(UpdateTranslationsRequest $request): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.edit');

        $validated = $request->validated();

        $this->translations->updateValues(
            $validated['locale'],
            $validated['translations'],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Translations updated.'),
        ]);

        return back();
    }
}
