<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        // Laravel mengubah TokenMismatchException jadi HttpException(419, ...) di
        // Handler::prepareException() SEBELUM callback render() dicek, jadi harus
        // ditangkap sebagai HttpException dan dicek status code-nya, bukan sebagai
        // TokenMismatchException (yang tidak akan pernah match di sini).
        $exceptions->render(function (HttpException $e, $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            return redirect()->route('login')
                ->with('status', 'Sesi kamu sudah berakhir, silakan login lagi.');
        });
    })->create();
