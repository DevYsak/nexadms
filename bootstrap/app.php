<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        then: function () {
            // Load iclock routes without the web middleware group.
            // Device endpoints don't need sessions, cookies, or CSRF.
            // LogBiometricRequest middleware is applied per-route inside iclock.php.
            \Illuminate\Support\Facades\Route::group(
                [],
                realpath(__DIR__.'/../../biometric-attendance/routes/iclock.php')
            );
        },
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // CSRF exemption kept as belt-and-suspenders in case web group is re-added.
        $middleware->validateCsrfTokens(except: [
            'iclock/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
