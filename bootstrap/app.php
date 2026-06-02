<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Process unprocessed raw logs every 2 minutes and recalculate today's sessions
        $schedule->command('attendance:process-backlog')->everyTwoMinutes()->withoutOverlapping();
    })
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        then: function () {
            $iclockRoutes = realpath(__DIR__.'/../../biometric-attendance/routes/iclock.php');
            if ($iclockRoutes) {
                \Illuminate\Support\Facades\Route::group([], $iclockRoutes);
            }
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
