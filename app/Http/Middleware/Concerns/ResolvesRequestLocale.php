<?php

namespace App\Http\Middleware\Concerns;

use Illuminate\Http\Request;

trait ResolvesRequestLocale
{
    protected function resolveLocale(Request $request): string
    {
        $available = array_keys(config('locale.available', []));
        $locale = $request->cookie('locale', config('app.locale'));

        if (! in_array($locale, $available, true)) {
            $locale = config('app.fallback_locale');
        }

        return $locale;
    }
}
