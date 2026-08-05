<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <script>
            document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'light');
        </script>

        <div class="guest-wrapper">
            <div class="guest-card">
                <div class="guest-card-header">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo STT Cipasung" class="guest-logo">
                    <div>
                        <h1 class="guest-title">Sistem Manajemen Peminjaman Inventaris STT Cipasung</h1>
                        <p class="guest-subtitle">Masuk untuk melanjutkan</p>
                    </div>
                </div>

                {{ $slot }}

                <p class="text-center mt-3 mb-0" style="font-size: 0.8rem; opacity: 0.6;">
                    &copy; {{ date('Y') }} <a href="https://sttcipasung.ac.id/" target="_blank" rel="noopener noreferrer">STT CIPASUNG</a>
                </p>
            </div>
        </div>
    </body>
</html>
