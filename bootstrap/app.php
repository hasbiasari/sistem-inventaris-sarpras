<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
         $middleware->alias([
        'role' => \App\Http\Middleware\CheckRole::class,
    ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Token CSRF basi (mis. habis logout terus submit form lama / tombol back)
        // -> lempar balik ke login dengan pesan, bukan tampilin halaman 419 mentah.
        $exceptions->render(function (TokenMismatchException $e, $request) {
            return redirect()->route('login')
                ->with('status', 'Sesi kamu sudah berakhir, silakan login lagi.');
        });
    })->create();
