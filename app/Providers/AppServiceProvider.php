<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

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
        Password::defaults(function (): Password {
            $password = Password::min(15)->max(128);

            return $this->app->isProduction() ? $password->uncompromised() : $password;
        });

        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(20)->by('login-ip:'.$request->ip());
        });

        RateLimiter::for('registration', function (Request $request): Limit {
            return Limit::perHour(5)->by('registration-ip:'.$request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request): array {
            $emailHash = hash('sha256', Str::lower((string) $request->input('email')));

            return [
                Limit::perHour(3)->by('password-reset-email:'.$emailHash),
                Limit::perHour(10)->by('password-reset-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('verification', function (Request $request): array {
            return [
                Limit::perMinute(3)->by('verification-user:'.($request->user()?->getAuthIdentifier() ?? $request->ip())),
                Limit::perHour(10)->by('verification-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('sensitive', function (Request $request): Limit {
            return Limit::perMinute(30)->by('sensitive:'.($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });
    }
}
