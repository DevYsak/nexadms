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

        DB::listen(function (QueryExecuted $query): void {
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
        });
    }
}
