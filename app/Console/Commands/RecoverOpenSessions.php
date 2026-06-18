<?php

namespace App\Console\Commands;

use App\Services\AttendanceRecalculationService;
use Illuminate\Console\Command;

class RecoverOpenSessions extends Command
{
    protected $signature   = 'attendance:recover-sessions {--dry-run : Show what would be recovered without writing}';
    protected $description = 'Pair open overnight sessions from past dates with next-day checkout punches.';

    public function handle(AttendanceRecalculationService $svc): int
    {
        if ($this->option('dry-run')) {
            $this->info('Dry-run mode — no changes will be saved.');
        }

        $this->info('Scanning for open sessions on past dates…');

        $result = $svc->recoverOpenSessions();

        $this->table(
            ['Outcome', 'Count'],
            [
                ['Recovered (overnight paired)', $result['recovered']],
                ['Still open (no next-day punch found)', $result['still_open']],
            ]
        );

        return self::SUCCESS;
    }
}
