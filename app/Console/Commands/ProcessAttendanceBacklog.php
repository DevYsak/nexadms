<?php

namespace App\Console\Commands;

use App\Models\BiometricAttendance;
use App\Services\AttendanceRecalculationService;
use App\Services\RawLogProcessorService;
use Illuminate\Console\Command;

class ProcessAttendanceBacklog extends Command
{
    protected $signature   = 'attendance:process-backlog {--date= : Specific date (Y-m-d), defaults to today}';
    protected $description = 'Process unprocessed raw logs and recalculate sessions — run this after server restart';

    public function handle(RawLogProcessorService $processor, AttendanceRecalculationService $recalc): int
    {
        $date = $this->option('date') ?? today()->toDateString();

        $this->info('Processing unprocessed ATTLOG raw logs…');
        $imported = $processor->processAll();
        $this->info("  → {$imported} attendance record(s) imported from raw logs.");

        $this->info("Recalculating sessions for {$date}…");
        $codes = BiometricAttendance::whereDate('punch_time', $date)
            ->distinct('employee_code')
            ->pluck('employee_code');

        if ($codes->isEmpty()) {
            $this->warn('  No attendance records found for this date.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($codes->count());
        $bar->start();
        foreach ($codes as $code) {
            $recalc->recalculate($code, $date);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info("Done — {$codes->count()} employee(s) recalculated.");

        return self::SUCCESS;
    }
}
