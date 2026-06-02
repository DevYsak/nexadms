<?php

namespace App\Services;

use App\Models\BiometricAttendance;
use App\Models\BiometricDevice;
use App\Models\BiometricRawLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RawLogProcessorService
{
    /**
     * Process all unprocessed ATTLOG raw logs.
     * Call this after server restart or on a schedule.
     */
    public function processAll(): int
    {
        $processed = 0;

        BiometricRawLog::where('log_type', 'ATTLOG')
            ->where('records_parsed', 0)
            ->whereNotNull('body')
            ->where('body', '!=', '')
            ->orderBy('id')
            ->chunk(50, function ($logs) use (&$processed) {
                foreach ($logs as $log) {
                    $count = $this->processRawLog($log);
                    $processed += $count;
                }
            });

        return $processed;
    }

    /**
     * Parse a single raw log body and upsert attendance records.
     * ATTLOG format (ZKTeco):
     *   PIN  DateTime    Verify  InOut  Reserved  WorkCode
     *   17   2026-06-01 07:31:44   1       0         0         0
     */
    public function processRawLog(BiometricRawLog $rawLog): int
    {
        $device = BiometricDevice::where('serial_number', $rawLog->serial_number)->first();
        $lines  = preg_split('/\r?\n/', trim($rawLog->body ?? ''));
        $count  = 0;

        DB::transaction(function () use ($lines, $rawLog, $device, &$count) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;

                $parts = preg_split('/\s+/', $line);
                // Minimum fields: PIN + DateTime (2 parts merged or split) + Verify + InOut
                if (count($parts) < 4) continue;

                $pin        = $parts[0];
                $dateTime   = $parts[1] . ' ' . $parts[2];   // "2026-06-01 07:31:44"
                $verifyType = (int) ($parts[3] ?? 0);
                $inOut      = (int) ($parts[4] ?? 0);         // 0=check_in, 1=check_out (device-set, often 0)

                try {
                    $punchTime = Carbon::parse($dateTime);
                } catch (\Throwable) {
                    continue;
                }

                // Upsert — prevent duplicates even if device resends
                BiometricAttendance::firstOrCreate(
                    [
                        'employee_code' => $pin,
                        'punch_time'    => $punchTime->toDateTimeString(),
                        'device_id'     => $device?->id,
                    ],
                    [
                        'verify_type' => $verifyType,
                        'event_type'  => 'unknown',   // Will be set by recalculation
                    ]
                );

                $count++;
            }

            // Mark this raw log as processed
            $rawLog->update(['records_parsed' => $count]);
        });

        return $count;
    }
}
