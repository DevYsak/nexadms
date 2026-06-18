<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Process unprocessed raw logs every 2 minutes and recalculate today's sessions
        $schedule->command('attendance:process-backlog')->everyTwoMinutes()->withoutOverlapping();
        // Recover open overnight sessions from past dates (runs once daily at 01:00)
        $schedule->command('attendance:recover-sessions')->dailyAt('01:00')->withoutOverlapping();
    })
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        then: function () {
            // Load iclock routes without web middleware (no CSRF, no sessions).
            \Illuminate\Support\Facades\Route::group(
                [],
                __DIR__.'/../routes/iclock.php'
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
