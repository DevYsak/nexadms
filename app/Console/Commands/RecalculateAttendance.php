<?php

namespace App\Console\Commands;

use App\Models\BiometricAttendance;
use App\Services\AttendanceRecalculationService;
use Illuminate\Console\Command;

class RecalculateAttendance extends Command
{
    protected $signature   = 'attendance:recalculate
                                {code : Employee code (e.g. 5)}
                                {date? : Date in Y-m-d format (default: today)}
                                {--all : Recalculate all employees for the given date}';

    protected $description = 'Force-recalculate attendance sessions for an employee and date.';

    public function handle(AttendanceRecalculationService $svc): int
    {
        $code = $this->argument('code');
        $date = $this->argument('date') ?? today()->toDateString();

        if ($this->option('all')) {
            $codes = BiometricAttendance::whereDate('punch_time', $date)
                ->distinct('employee_code')
                ->pluck('employee_code');

            $this->info("Recalculating {$codes->count()} employees for {$date}…");

            foreach ($codes as $c) {
                $svc->recalculate($c, $date);
                $this->line("  ✓ {$c}");
            }

            $this->info('Done.');
            return self::SUCCESS;
        }

        $this->info("Recalculating employee {$code} for {$date}…");
        $svc->recalculate($code, $date);
        $this->info('Done.');

        return self::SUCCESS;
    }
}
