<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        RateLimiter::for('login', function (Request $request) {
            $username = Str::lower((string) $request->input('username'));

            return [
                Limit::perMinute(8)->by($username.'|'.$request->ip()),
                Limit::perMinute(30)->by((string) $request->ip()),
            ];
        });

        RateLimiter::for('password-recovery', function (Request $request) {
            $username = Str::lower((string) $request->input('recovery_username'));

            return [
                Limit::perMinute(4)->by($username.'|'.$request->ip()),
                Limit::perMinute(20)->by((string) $request->ip()),
            ];
        });

        RateLimiter::for('internal', function (Request $request) {
            $sessionUserId = $request->hasSession()
                ? $request->session()->get('auth_user_id')
                : null;

            return Limit::perMinute(180)->by((string) ($sessionUserId ?: $request->ip()));
        });
    }
}
