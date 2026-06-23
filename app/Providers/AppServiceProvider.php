<?php

namespace App\Providers;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Hr\Contractor;
use App\Models\Hr\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureMorphMap();
        $this->configureGates();
        $this->configureInertiaExceptionHandling();
        $this->refreshViteFontManifestCacheInLocal();
    }

    /**
     * Vite regenerates fonts-manifest.dev.json when the dev server starts or
     * restarts. php artisan serve keeps the old manifest in memory until it is
     * restarted, which causes 404s for hashed font URLs.
     */
    protected function refreshViteFontManifestCacheInLocal(): void
    {
        if (! $this->app->environment('local')) {
            return;
        }

        static $lastManifestMtime = null;

        $this->app->booted(function () use (&$lastManifestMtime): void {
            $manifestPath = public_path('fonts-manifest.dev.json');

            if (! is_file($manifestPath)) {
                return;
            }

            $mtime = filemtime($manifestPath);

            if ($lastManifestMtime !== null && $lastManifestMtime !== $mtime) {
                Vite::flush();
            }

            $lastManifestMtime = $mtime;
        });
    }

    protected function configureMorphMap(): void
    {
        Relation::morphMap([
            'employee' => Employee::class,
            'contractor' => Contractor::class,
        ]);
    }

    protected function configureGates(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Owner') ? true : null;
        });
    }

    protected function configureInertiaExceptionHandling(): void
    {
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
            $component = match ($response->statusCode()) {
                403 => 'errors/Forbidden',
                404 => 'errors/NotFound',
                default => null,
            };

            if ($component === null) {
                return;
            }

            return $response
                ->render($component)
                ->usingMiddleware(HandleInertiaRequests::class)
                ->withSharedData();
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
