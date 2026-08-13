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
        // buat maksa pakai https pas diakses lewat ngrok atau cloudflare tunnel, biar css/js/redirect gak keblokir mixed content.
        // dicek dari header X-Forwarded-Proto request yang lagi jalan (bukan dari APP_URL di .env), soalnya URL ngrok gratisan
        // ganti-ganti tiap restart -- kalau APP_URL ketinggalan lama sementara diaksesnya udah bukan lewat tunnel itu lagi
        // (misal langsung dari localhost/XAMPP), redirect abis submit form bakal maksa https padahal servernya gak ada TLS,
        // jadi halaman abis submit gagal kebuka & semua popup/notifikasi sukses gak pernah keliatan.
        if (request()->header('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }
    }
}