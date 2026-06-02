<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\BiometricAttendance;
use Carbon\Carbon;

class AttendanceRecalculationService
{
    // Punches within this many seconds of the previous punch are duplicates.
    private const DEDUP_SECONDS = 90;

    public function recalculate(string $employeeCode, string $date): void
    {
        AttendanceSession::where('employee_code', $employeeCode)
            ->where('session_date', $date)
            ->delete();

        $punches = BiometricAttendance::where('employee_code', $employeeCode)
            ->whereDate('punch_time', $date)
            ->orderBy('punch_time')
            ->get();

        $this->buildSessions($employeeCode, $date, $punches);
    }

    public function recalculateToday(): void
    {
        $date  = today()->toDateString();
        $codes = BiometricAttendance::whereDate('punch_time', $date)
            ->distinct('employee_code')
            ->pluck('employee_code');

        foreach ($codes as $code) {
            $this->recalculate($code, $date);
        }
    }

    private function buildSessions(string $code, string $date, $punches): void
    {
        if ($punches->isEmpty()) return;

        // Step 1 — deduplicate: mark punches within DEDUP_SECONDS of previous as skipped.
        $deduped   = [];
        $skipped   = [];
        $prevTime  = null;

        foreach ($punches as $punch) {
            $t = Carbon::parse($punch->punch_time);
            if ($prevTime && $t->diffInSeconds($prevTime) <= self::DEDUP_SECONDS) {
                $skipped[] = $punch->id;
            } else {
                $deduped[] = $punch;
                $prevTime  = $t;
            }
        }

        // Mark skipped punches in DB
        if (!empty($skipped)) {
            BiometricAttendance::whereIn('id', $skipped)
                ->update(['event_type' => 'skipped']);
        }

        // Step 2 — alternate pairing: 1st=check_in, 2nd=check_out, 3rd=check_in …
        $index   = 0;
        $checkIn = null;
        $checkInId = null;

        foreach ($deduped as $punch) {
            if ($checkIn === null) {
                $checkIn   = $punch->punch_time;
                $checkInId = $punch->id;
                BiometricAttendance::where('id', $punch->id)->update(['event_type' => 'check_in']);
            } else {
                $checkOut   = $punch->punch_time;
                $mins       = Carbon::parse($checkIn)->diffInMinutes($checkOut);

                BiometricAttendance::where('id', $punch->id)->update(['event_type' => 'check_out']);

                AttendanceSession::create([
                    'employee_code'    => $code,
                    'session_date'     => $date,
                    'session_index'    => $index++,
                    'check_in_at'      => $checkIn,
                    'check_out_at'     => $checkOut,
                    'duration_minutes' => $mins,
                    'status'           => 'present',
                    'is_overnight'     => false,
                ]);

                $checkIn   = null;
                $checkInId = null;
            }
        }

        // Unpaired last punch — still inside
        if ($checkIn !== null) {
            AttendanceSession::create([
                'employee_code' => $code,
                'session_date'  => $date,
                'session_index' => $index,
                'check_in_at'   => $checkIn,
                'check_out_at'  => null,
                'status'        => 'in_office',
                'is_overnight'  => false,
            ]);
        }
    }
}
