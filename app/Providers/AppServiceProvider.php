<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;

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
        // 1. Paksa HTTPS jika di Vercel/Production
        // Ini penting agar link stream tidak diblokir browser atau game
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }

        // 2. Gunakan styling Tailwind untuk pagination tabel lagu kamu
        Paginator::useTailwind();
    }
}
