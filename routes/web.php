<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Website\ContactController;
use App\Http\Controllers\Website\WebsiteController;
use Illuminate\Support\Facades\Route;

Route::post('locale/{locale}', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/', [WebsiteController::class, 'home'])->name('website.home');
Route::get('/about', [WebsiteController::class, 'about'])->name('website.about');
Route::get('/what-we-do', [WebsiteController::class, 'whatWeDo'])->name('website.what-we-do');
Route::get('/services', [WebsiteController::class, 'services'])->name('website.services');
Route::get('/services/{slug}', [WebsiteController::class, 'service'])->name('website.services.show');
Route::get('/projects', [WebsiteController::class, 'projects'])->name('website.projects');
Route::get('/projects/ongoing', [WebsiteController::class, 'projects'])
    ->defaults('filter', 'ongoing')
    ->name('website.projects.ongoing');
Route::get('/projects/completed', [WebsiteController::class, 'projects'])
    ->defaults('filter', 'completed')
    ->name('website.projects.completed');
Route::get('/projects/{slug}', [WebsiteController::class, 'project'])
    ->where('slug', '^[A-Za-z0-9][A-Za-z0-9\-]*$')
    ->name('website.projects.show');
Route::get('/trainings', [WebsiteController::class, 'trainings'])->name('website.trainings');
Route::get('/trainings/{slug}', [WebsiteController::class, 'training'])->name('website.trainings.show');
Route::get('/certificates', [WebsiteController::class, 'certificates'])->name('website.certificates');
Route::get('/contact', [WebsiteController::class, 'contact'])->name('website.contact');
Route::post('/contact', [ContactController::class, 'store'])->name('website.contact.store');

Route::redirect('/favicon.ico', '/favicon.svg');

require __DIR__.'/mis.php';
require __DIR__.'/settings.php';
require __DIR__.'/notifications.php';
