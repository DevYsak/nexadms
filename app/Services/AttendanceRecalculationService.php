<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\BiometricAttendance;
use Carbon\Carbon;

class AttendanceRecalculationService
{
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

        $index   = 0;
        $checkIn = null;

        foreach ($punches as $punch) {
            if ($checkIn === null) {
                $checkIn = $punch->punch_time;
            } else {
                $checkOut = $punch->punch_time;
                $mins     = Carbon::parse($checkIn)->diffInMinutes($checkOut);

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

                $checkIn = null;
            }
        }

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
