<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\ResolvesRequestLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    use ResolvesRequestLocale;
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);
        $localeConfig = config('locale.available.'.$locale, []);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() ? [
                    ...$request->user()->toArray(),
                    'roles' => $request->user()->getRoleNames()->values()->all(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name')->values()->all(),
                ] : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'locale' => $locale,
            'dir' => $localeConfig['dir'] ?? 'ltr',
            'locales' => collect(config('locale.available', []))
                ->map(fn (array $config, string $code): array => [
                    'code' => $code,
                    'name' => $config['name'],
                    'native' => $config['native'],
                    'dir' => $config['dir'],
                ])
                ->values()
                ->all(),
            'translations' => $this->translationsFor($locale),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function translationsFor(string $locale): array
    {
        $fallbackLocale = config('app.fallback_locale', 'en');
        $fallback = $this->loadJsonTranslations($fallbackLocale);
        $current = $this->loadJsonTranslations($locale);

        return array_merge($fallback, $current);
    }

    /**
     * @return array<string, string>
     */
    protected function loadJsonTranslations(string $locale): array
    {
        $path = lang_path("{$locale}.json");

        if (! File::exists($path)) {
            return [];
        }

        /** @var array<string, string> $translations */
        $translations = File::json($path);

        return $translations;
    }
}
