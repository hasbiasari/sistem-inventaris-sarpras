<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // buat maksa pakai https pas diakses lewat ngrok atau cloudflare tunnel, biar css/js gak keblokir mixed content
        if (str_contains(config('app.url'), 'ngrok') || str_contains(config('app.url'), 'trycloudflare')) {
            URL::forceScheme('https');
        }
    }
}