<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        RateLimiter::for('login', function (Request $request): array {
            $ip = $request->ip() ?? 'unknown';
            $limits = [
                Limit::perMinute(300)->by('login:ip:'.$ip),
            ];
            $email = Str::lower(Str::transliterate(trim((string) $request->input('email', ''))));

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $limits[] = Limit::perMinute(5)->by('login:ip-email:'.$ip.':'.$email);
                $limits[] = Limit::perMinute(10)->by('login:email:'.$email);
            }

            return $limits;
        });
    }
}
