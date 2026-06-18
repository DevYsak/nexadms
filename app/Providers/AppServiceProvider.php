<?php

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

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
        $this->loadMigrationsFrom(realpath(__DIR__.'/../../../biometric-attendance/database/migrations'));

        // Optional debug logging of attendance inserts. Gated behind a flag and
        // wrapped in try/catch so a logging failure (e.g. an unwritable log file)
        // can NEVER crash a device push and trigger an ATTLOG resend loop.
        if (env('BIOMETRIC_SQL_LOG', false)) {
            DB::listen(function (QueryExecuted $query): void {
                try {
                    $sql = strtolower($query->sql);
                    if (! str_contains($sql, 'biometric_attendances')) {
                        return;
                    }

                    if (! str_starts_with(ltrim($sql), 'insert')) {
                        return;
                    }

                    Log::channel('biometric')->debug('SQL insert executed', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time_ms' => $query->time,
                        'connection' => $query->connectionName,
                    ]);
                } catch (\Throwable) {
                    // Never let debug logging break the request.
                }
            });
        }
    }
}
