<?php

use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::post('locale/{locale}', [LocaleController::class, 'update'])->name('locale.update');

Route::inertia('/', 'Welcome')->name('home');

Route::redirect('/favicon.ico', '/favicon.svg');

require __DIR__.'/mis.php';
require __DIR__.'/settings.php';
