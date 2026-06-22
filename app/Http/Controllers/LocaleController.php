<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function update(string $locale): RedirectResponse
    {
        if (! array_key_exists($locale, config('locale.available', []))) {
            abort(404);
        }

        return back()
            ->cookie('locale', $locale, 60 * 24 * 365);
    }
}
