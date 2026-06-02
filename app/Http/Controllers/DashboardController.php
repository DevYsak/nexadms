<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\BiometricAttendance;
use App\Models\BiometricDevice;
use App\Models\BiometricRawLog;
use App\Models\Employee;
use App\Services\AttendanceRecalculationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // =========================================================================
    // Main Dashboard
    // =========================================================================

    public function index(Request $request)
    {
        $date       = $request->input('date', today()->toDateString());
        $department = $request->input('department', '');
        $deviceId   = $request->input('device', '');
        $search     = $request->input('search', '');
        $perPage    = (int) $request->input('per_page', 10);

        $departments = Employee::departments();
        $devices     = BiometricDevice::orderBy('serial_number')->get();

        $stats = $this->buildStats($date);

        return view('attendance.dashboard', compact(
            'date', 'department', 'deviceId', 'search',
            'departments', 'devices', 'stats', 'perPage'
        ));
    }

    // =========================================================================
    // AJAX — Attendance Grid
    // =========================================================================

    public function gridApi(Request $request): JsonResponse
    {
        $date       = $request->input('date', today()->toDateString());
        $department = $request->input('department', '');
        $deviceId   = $request->input('device', '');
        $search     = $request->input('search', '');
        $page       = max(1, (int) $request->input('page', 1));
        $perPage    = max(5, min(50, (int) $request->input('per_page', 10)));

        // Use filled() — ConvertEmptyStringsToNull middleware turns "" into null,
        // so checking !== '' misses nulls and adds WHERE column = NULL (matches nothing).
        $query = Employee::active()->orderBy('name');

        if (filled($department)) {
            $query->where('department', $department);
        }
        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        $total     = (clone $query)->count();
        $employees = (clone $query)->skip(($page - 1) * $perPage)->take($perPage)->get();

        $codes = $employees->pluck('employee_code')->toArray();

        // ── Read from attendance_sessions (computed by AttendancePairingService) ──
        // This gives accurate first_in / last_out / working_hours even when
        // the server was offline and records arrived out of order.
        $sessionSummaries = AttendanceSession::dailySummaryFor($codes, $date);

        // Raw punch counts per employee (for the punch_count column)
        $punchCountQuery = BiometricAttendance::whereIn('employee_code', $codes)
            ->whereDate('punch_time', $date);
        if (filled($deviceId)) {
            $punchCountQuery->where('device_id', $deviceId);
        }
        $punchCounts = $punchCountQuery
            ->selectRaw('employee_code, COUNT(*) as cnt, MIN(punch_time) as first_punch')
            ->groupBy('employee_code')
            ->get()
            ->keyBy('employee_code');

        $rows = $employees->map(function (Employee $emp) use ($sessionSummaries, $punchCounts, $date) {
            $summary    = $sessionSummaries[$emp->employee_code] ?? null;
            $punchRow   = $punchCounts->get($emp->employee_code);
            $punchCount = $punchRow?->cnt ?? 0;

            if (! $summary || $punchCount === 0) {
                return [
                    'code'          => $emp->employee_code,
                    'name'          => $emp->name,
                    'department'    => $emp->department,
                    'initials'      => $emp->initials,
                    'avatar_color'  => $emp->avatar_color,
                    'punch_count'   => 0,
                    'first_in'      => null,
                    'last_out'      => null,
                    'working_hours' => null,
                    'status'        => 'absent',
                    'device_sn'     => null,
                    'verify_label'  => null,
                ];
            }

            // Late arrival check using FIRST session check-in
            $firstInStr = $summary['first_in'];
            $isLate     = false;
            if ($firstInStr) {
                $firstIn   = Carbon::parse($date . ' ' . Carbon::parse($firstInStr)->format('H:i:s'));
                $shiftStart = Carbon::parse($date . ' ' . $emp->shift_start);
                $lateLimit  = $shiftStart->addMinutes($emp->late_threshold_minutes);
                $isLate     = $firstIn->gt($lateLimit);
            }

            $status = $summary['status'];
            if ($isLate && in_array($status, ['present', 'in_office'])) {
                $status = 'late';
            }

            return [
                'code'          => $emp->employee_code,
                'name'          => $emp->name,
                'department'    => $emp->department,
                'initials'      => $emp->initials,
                'avatar_color'  => $emp->avatar_color,
                'punch_count'   => $punchCount,
                'first_in'      => $summary['first_in'],
                'last_out'      => $summary['last_out'],
                'working_hours' => $summary['working_hours'],
                'status'        => $status,
                'device_sn'     => $punchRow ? '-' : null,
                'verify_label'  => null,
            ];
        });

        return response()->json([
            'rows'  => $rows->values(),
            'total' => $total,
            'page'  => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / $perPage),
        ]);
    }

    // =========================================================================
    // AJAX — Employee Timeline
    // =========================================================================

    public function timelineApi(Request $request, string $code): JsonResponse
    {
        $date     = $request->input('date', today()->toDateString());
        $employee = Employee::where('employee_code', $code)->first();

        $punches = BiometricAttendance::where('employee_code', $code)
            ->whereDate('punch_time', $date)
            ->orderBy('punch_time')
            ->get();

        $empData = $employee ? [
            'name'       => $employee->name,
            'department' => $employee->department,
            'initials'   => $employee->initials,
            'color'      => $employee->avatar_color,
        ] : ['name' => 'Employee ' . $code, 'department' => '-', 'initials' => substr($code, 0, 2), 'color' => '#6366f1'];

        if ($punches->isEmpty()) {
            return response()->json([
                'employee' => $empData,
                'sessions' => [],
                'events'   => [],
                'summary'  => null,
            ]);
        }

        // Sessions from AttendanceSession table (computed pairings)
        $sessions = AttendanceSession::where('employee_code', $code)
            ->where('session_date', $date)
            ->orderBy('session_index')
            ->get()
            ->map(fn ($s) => [
                'index'        => $s->session_index + 1,
                'check_in'     => $s->check_in_at?->format('h:i A'),
                'check_out'    => $s->check_out_at?->format('h:i A'),
                'duration'     => $s->duration_human,
                'status'       => $s->status,
                'status_label' => $s->status_label,
                'is_overnight' => $s->is_overnight,
                'admin_note'   => $s->admin_note,
            ]);

        // Raw events with duplicate detection
        $typeMap = [
            'check_in'  => ['label' => 'Check-In',  'type' => 'check_in'],
            'check_out' => ['label' => 'Check-Out', 'type' => 'check_out'],
            'skipped'   => ['label' => 'Duplicate', 'type' => 'duplicate'],
            'unknown'   => ['label' => 'Scan',      'type' => 'unknown'],
        ];

        $events = $punches->map(function ($punch) use ($typeMap) {
            $time = Carbon::parse($punch->punch_time);
            $et   = $typeMap[$punch->event_type] ?? $typeMap['unknown'];
            return [
                'type'         => $et['type'],
                'label'        => $et['label'],
                'time'         => $time->format('h:i A'),
                'time_full'    => $time->format('h:i:s A'),
                'verify_label' => $punch->verify_type_label,
                'is_duplicate' => in_array($punch->event_type, ['skipped']),
            ];
        });

        // Summary
        $sessions_col  = $sessions->collect();
        $firstSession  = $sessions_col->first();
        $lastSession   = $sessions_col->last();
        $firstIn  = $firstSession ? $firstSession['check_in'] : Carbon::parse($punches->first()->punch_time)->format('h:i A');
        $lastOut  = $lastSession  ? $lastSession['check_out']  : null;
        $totalMins = AttendanceSession::where('employee_code', $code)
            ->where('session_date', $date)
            ->sum('duration_minutes');

        $dupCount = $events->where('is_duplicate', true)->count();

        $status = 'in_office';
        if ($lastOut) $status = 'checked_out';
        elseif ($punches->isEmpty()) $status = 'absent';

        $summary = [
            'first_in'        => $firstIn,
            'last_out'        => $lastOut,
            'total_sessions'  => $sessions->count(),
            'total_punches'   => $punches->count(),
            'duplicate_count' => $dupCount,
            'working_hours'   => $totalMins ? sprintf('%dh %02dm', intdiv($totalMins, 60), $totalMins % 60) : null,
            'status'          => $status,
        ];

        return response()->json([
            'employee' => $empData,
            'sessions' => $sessions->values(),
            'events'   => $events->values(),
            'summary'  => $summary,
        ]);
    }

    // =========================================================================
    // AJAX — Stats
    // =========================================================================

    public function statsApi(Request $request): JsonResponse
    {
        $date = $request->input('date', today()->toDateString());
        return response()->json($this->buildStats($date));
    }

    // =========================================================================
    // AJAX — Sync Status
    // =========================================================================

    public function syncStatusApi(): JsonResponse
    {
        $devices = BiometricDevice::all()->map(function (BiometricDevice $d) {
            $lastActivity = $d->last_activity_at;
            $isOnline     = $lastActivity && $lastActivity->diffInMinutes(now()) <= 5;

            $lastLog = BiometricRawLog::where('serial_number', $d->serial_number)
                ->where('log_type', 'ATTLOG')
                ->latest('id')
                ->first();

            return [
                'serial_number'  => $d->serial_number,
                'name'           => $d->name ?? $d->serial_number,
                'firmware'       => $d->firmware_version ?? $d->pushver ?? 'Unknown',
                'is_online'      => $isOnline,
                'last_activity'  => $lastActivity?->diffForHumans() ?? 'Never',
                'last_activity_ts' => $lastActivity?->toDateTimeString() ?? null,
                'last_attlog'    => $lastLog ? Carbon::parse($lastLog->received_at)->toDateTimeString() : null,
                'records_today'  => BiometricAttendance::where('device_id', $d->id)
                    ->whereDate('punch_time', today())->count(),
            ];
        });

        $totalUnsynced  = BiometricRawLog::where('log_type', 'ATTLOG')
            ->where('records_parsed', 0)
            ->whereNotNull('body')
            ->where('body', '!=', '')
            ->count();

        return response()->json([
            'devices'          => $devices->values(),
            'total_unsynced'   => $totalUnsynced,
            'last_sync'        => BiometricDevice::max('last_activity_at'),
            'server_time'      => now()->timezone(config('biometric.timezone', 'Asia/Kolkata'))->format('d M Y h:i:s A'),
        ]);
    }

    // =========================================================================
    // Admin Correction
    // =========================================================================

    /** GET /admin/sessions?employee_code=X&date=Y */
    public function adminSessions(Request $request): JsonResponse
    {
        $code = (string) $request->input('employee_code', '');
        $date = (string) $request->input('date', today()->toDateString());

        $sessions = AttendanceSession::where('employee_code', $code)
            ->where('session_date', $date)
            ->orderBy('session_index')
            ->get()
            ->map(fn (AttendanceSession $s) => [
                'id'             => $s->id,
                'session_index'  => $s->session_index,
                'check_in_at'    => $s->check_in_at?->format('Y-m-d H:i:s'),
                'check_out_at'   => $s->check_out_at?->format('Y-m-d H:i:s'),
                'duration_human' => $s->duration_human,
                'status'         => $s->status,
                'status_label'   => $s->status_label,
                'is_overnight'   => $s->is_overnight,
                'admin_note'     => $s->admin_note,
            ]);

        $rawEvents = BiometricAttendance::where('employee_code', $code)
            ->whereDate('punch_time', $date)
            ->with('device')
            ->orderBy('punch_time')
            ->get()
            ->map(fn ($e) => [
                'id'           => $e->id,
                'punch_time'   => Carbon::parse($e->punch_time)->format('Y-m-d H:i:s'),
                'event_type'   => $e->event_type,
                'session_id'   => $e->session_id,
                'verify_label' => $e->verify_type_label,
                'device_sn'    => $e->device?->serial_number ?? '-',
            ]);

        return response()->json([
            'employee_code' => $code,
            'date'          => $date,
            'sessions'      => $sessions,
            'raw_events'    => $rawEvents,
        ]);
    }

    /** PATCH /admin/sessions/{id} — edit check_in_at or check_out_at */
    public function adminUpdateSession(Request $request, int $id): JsonResponse
    {
        $session = AttendanceSession::findOrFail($id);

        $checkIn  = $request->input('check_in_at');
        $checkOut = $request->input('check_out_at');
        $note     = $request->input('admin_note', '');

        $updates = [
            'status'       => 'admin_corrected',
            'admin_note'   => $note,
            'corrected_by' => $request->input('corrected_by', 'Admin'),
            'corrected_at' => now(),
        ];

        if ($checkIn) {
            $updates['check_in_at'] = Carbon::parse($checkIn);
        }
        if ($checkOut) {
            $updates['check_out_at'] = Carbon::parse($checkOut);
        }
        if ($checkIn && $checkOut) {
            $updates['duration_minutes'] = Carbon::parse($checkIn)->diffInMinutes(Carbon::parse($checkOut));
        }

        $session->update($updates);

        return response()->json(['ok' => true, 'session' => $session->fresh()]);
    }

    /** POST /admin/sessions/recalculate — force rebuild sessions for employee+date */
    public function adminRecalculate(Request $request): JsonResponse
    {
        $code = (string) $request->input('employee_code', '');
        $date = (string) $request->input('date', today()->toDateString());

        if ($code === '' || $date === '') {
            return response()->json(['error' => 'employee_code and date are required'], 422);
        }

        app(AttendanceRecalculationService::class)->recalculate($code, $date);

        return response()->json(['ok' => true, 'message' => "Sessions rebuilt for {$code} on {$date}"]);
    }

    /** POST /admin/sessions/recalculate-all — rebuild all sessions for today */
    public function adminRecalculateAll(): JsonResponse
    {
        app(AttendanceRecalculationService::class)->recalculateToday();
        return response()->json(['ok' => true, 'message' => 'All sessions rebuilt for today.']);
    }

    // =========================================================================
    // Reports
    // =========================================================================

    public function reports(Request $request)
    {
        $date  = $request->input('date', today()->toDateString());
        $month = $request->input('month', now()->format('Y-m'));
        return view('attendance.reports', compact('date', 'month'));
    }

    public function reportApi(Request $request): JsonResponse
    {
        $type  = $request->input('type', 'daily');
        $date  = $request->input('date', today()->toDateString());
        $month = $request->input('month', now()->format('Y-m'));

        if ($type === 'daily') {
            return $this->dailyReport($date);
        }

        if ($type === 'monthly') {
            return $this->monthlyReport($month);
        }

        return response()->json(['error' => 'Unknown report type'], 422);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function buildStats(string $date): array
    {
        $totalEmployees = Employee::active()->count();

        // Employees who punched today
        $presentCodes = BiometricAttendance::whereDate('punch_time', $date)
            ->distinct('employee_code')
            ->pluck('employee_code')
            ->toArray();

        $presentCount = count($presentCodes);
        $absentCount  = max(0, $totalEmployees - $presentCount);

        // Late arrivals — first punch after shift_start + threshold
        $lateCount = Employee::active()
            ->whereIn('employee_code', $presentCodes)
            ->get()
            ->filter(function (Employee $emp) use ($date, $presentCodes) {
                if (! in_array($emp->employee_code, $presentCodes)) return false;
                $firstPunch = BiometricAttendance::where('employee_code', $emp->employee_code)
                    ->whereDate('punch_time', $date)
                    ->min('punch_time');
                if (! $firstPunch) return false;
                $limit = Carbon::parse($date . ' ' . $emp->shift_start)->addMinutes($emp->late_threshold_minutes);
                return Carbon::parse($firstPunch)->gt($limit);
            })->count();

        $totalPunches = BiometricAttendance::whereDate('punch_time', $date)->count();

        $devicesOnline = BiometricDevice::where('last_activity_at', '>=', now()->subMinutes(5))->count();

        // Hourly punch distribution for chart
        $hourlyData = BiometricAttendance::whereDate('punch_time', $date)
            ->selectRaw('HOUR(punch_time) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $hourly = [];
        for ($h = 6; $h <= 22; $h++) {
            $hourly[] = ['hour' => sprintf('%02d:00', $h), 'count' => $hourlyData[$h] ?? 0];
        }

        return [
            'total_employees' => $totalEmployees,
            'present'         => $presentCount,
            'absent'          => $absentCount,
            'late'            => $lateCount,
            'total_punches'   => $totalPunches,
            'devices_online'  => $devicesOnline,
            'check_ins'       => $presentCount,
            'hourly'          => $hourly,
        ];
    }

    private function dailyReport(string $date): JsonResponse
    {
        $employees = Employee::active()->orderBy('name')->get();
        $codes     = $employees->pluck('employee_code')->toArray();

        $punchGroups = BiometricAttendance::whereIn('employee_code', $codes)
            ->whereDate('punch_time', $date)
            ->orderBy('punch_time')
            ->get()
            ->groupBy('employee_code');

        $rows = $employees->map(function (Employee $emp) use ($punchGroups, $date) {
            $punches = $punchGroups->get($emp->employee_code, collect());

            if ($punches->isEmpty()) {
                return ['code' => $emp->employee_code, 'name' => $emp->name, 'department' => $emp->department, 'first_in' => '-', 'last_out' => '-', 'working_hours' => '-', 'punches' => 0, 'status' => 'Absent'];
            }

            $first = Carbon::parse($punches->first()->punch_time);
            $last  = $punches->count() > 1 ? Carbon::parse($punches->last()->punch_time) : null;
            $mins  = $last ? $first->diffInMinutes($last) : null;

            $shiftStart = Carbon::parse($date . ' ' . $emp->shift_start);
            $isLate     = $first->gt($shiftStart->addMinutes($emp->late_threshold_minutes));

            return [
                'code'          => $emp->employee_code,
                'name'          => $emp->name,
                'department'    => $emp->department,
                'first_in'      => $first->format('h:i A'),
                'last_out'      => $last?->format('h:i A') ?? 'In Office',
                'working_hours' => $mins !== null ? sprintf('%dh %dm', intdiv($mins, 60), $mins % 60) : '-',
                'punches'       => $punches->count(),
                'status'        => $last ? ($isLate ? 'Late' : 'Present') : 'In Office',
            ];
        });

        return response()->json(['date' => $date, 'rows' => $rows->values()]);
    }

    private function monthlyReport(string $month): JsonResponse
    {
        [$year, $mon] = explode('-', $month);
        $daysInMonth  = Carbon::create($year, $mon, 1)->daysInMonth;

        $employees = Employee::active()->orderBy('name')->get();
        $codes     = $employees->pluck('employee_code')->toArray();

        $punchGroups = BiometricAttendance::whereIn('employee_code', $codes)
            ->whereYear('punch_time', $year)
            ->whereMonth('punch_time', $mon)
            ->selectRaw('employee_code, DATE(punch_time) as punch_date, COUNT(*) as cnt')
            ->groupBy('employee_code', 'punch_date')
            ->get()
            ->groupBy('employee_code');

        $rows = $employees->map(function (Employee $emp) use ($punchGroups, $daysInMonth) {
            $days    = $punchGroups->get($emp->employee_code, collect())->pluck('cnt', 'punch_date')->toArray();
            $present = count($days);
            $absent  = $daysInMonth - $present;

            return [
                'code'       => $emp->employee_code,
                'name'       => $emp->name,
                'department' => $emp->department,
                'present'    => $present,
                'absent'     => max(0, $absent),
                'total_days' => $daysInMonth,
            ];
        });

        return response()->json(['month' => $month, 'rows' => $rows->values()]);
    }
}
