<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceApiController extends Controller
{
    // ── GET /api/attendance/today ─────────────────────────────────────────────

    public function today(): JsonResponse
    {
        return $this->forDate(now()->toDateString());
    }

    // ── GET /api/attendance/date/{date} ───────────────────────────────────────

    public function byDate(string $date): JsonResponse
    {
        if (! $this->validDate($date)) {
            return response()->json(['error' => 'Invalid date. Use Y-m-d format.'], 422);
        }

        return $this->forDate($date);
    }

    // ── GET /api/attendance/employee/{employee_code} ──────────────────────────
    // Optional query params: ?from=Y-m-d&to=Y-m-d  (default: last 30 days)

    public function byEmployee(Request $request, string $code): JsonResponse
    {
        $employee = Employee::where('employee_code', $code)->first();

        if (! $employee) {
            return response()->json(['error' => 'Employee not found.'], 404);
        }

        $toStr   = $request->query('to',   now()->toDateString());
        $fromStr = $request->query('from', now()->subDays(29)->toDateString());

        if (! $this->validDate($fromStr) || ! $this->validDate($toStr)) {
            return response()->json(['error' => 'Invalid date range. Use Y-m-d format.'], 422);
        }

        $from = Carbon::parse($fromStr);
        $to   = Carbon::parse($toStr);

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        // Cap range at 90 days to prevent unbounded queries
        if ($from->diffInDays($to) > 90) {
            $from = $to->copy()->subDays(89);
        }

        $sessionsByDate = AttendanceSession::where('employee_code', $code)
            ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('session_date')
            ->orderBy('session_index')
            ->get()
            ->groupBy('session_date');

        $records = [];
        $cursor  = $from->copy();

        while ($cursor <= $to) {
            $d           = $cursor->toDateString();
            $daySessions = $sessionsByDate->get($d, collect());

            $records[] = [
                'date'     => $d,
                'sessions' => $daySessions->map(fn ($s) => $this->sessionShape($s))->values(),
                'summary'  => $this->summarise($daySessions),
            ];

            $cursor->addDay();
        }

        return response()->json([
            'employee_code' => $employee->employee_code,
            'name'          => $employee->name,
            'department'    => $employee->department,
            'designation'   => $employee->designation,
            'from'          => $from->toDateString(),
            'to'            => $to->toDateString(),
            'records'       => $records,
        ]);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function forDate(string $date): JsonResponse
    {
        $employees = Employee::active()->orderBy('name')->get();
        $codes     = $employees->pluck('employee_code')->toArray();

        $sessionsByCode = AttendanceSession::whereIn('employee_code', $codes)
            ->where('session_date', $date)
            ->orderBy('session_index')
            ->get()
            ->groupBy('employee_code');

        $rows = $employees->map(function ($emp) use ($sessionsByCode) {
            $sessions = $sessionsByCode->get($emp->employee_code, collect());

            return [
                'employee_code' => $emp->employee_code,
                'name'          => $emp->name,
                'department'    => $emp->department,
                'designation'   => $emp->designation,
                'sessions'      => $sessions->map(fn ($s) => $this->sessionShape($s))->values(),
                'summary'       => $this->summarise($sessions),
            ];
        });

        return response()->json([
            'date'         => $date,
            'generated_at' => now()->toIso8601String(),
            'total'        => $rows->count(),
            'employees'    => $rows->values(),
        ]);
    }

    private function sessionShape($session): array
    {
        return [
            'index'            => $session->session_index,
            'check_in_at'      => $session->check_in_at?->toDateTimeString(),
            'check_out_at'     => $session->check_out_at?->toDateTimeString(),
            'duration_minutes' => $session->duration_minutes,
            'status'           => $session->status,
            'is_overnight'     => $session->is_overnight,
            'admin_note'       => $session->admin_note,
        ];
    }

    private function summarise($sessions): array
    {
        if ($sessions->isEmpty()) {
            return [
                'first_in'      => null,
                'last_out'      => null,
                'total_minutes' => 0,
                'working_hours' => null,
                'punch_count'   => 0,
                'status'        => 'absent',
            ];
        }

        $sorted      = $sessions->sortBy('session_index');
        $lastSession = $sorted->last();
        $lastOut     = $sorted->filter(fn ($s) => $s->check_out_at !== null)->last();
        $totalMins   = (int) $sessions->sum('duration_minutes');

        // Overall status: admin_corrected wins; then check if still in office
        if ($sessions->contains('status', 'admin_corrected')) {
            $status = 'admin_corrected';
        } elseif ($lastSession->check_out_at !== null) {
            $status = 'checked_out';
        } else {
            $status = 'in_office';
        }

        $punchCount = $sessions->sum(
            fn ($s) => ($s->check_in_at ? 1 : 0) + ($s->check_out_at ? 1 : 0)
        );

        return [
            'first_in'      => $sorted->first()->check_in_at?->format('h:i A'),
            'last_out'      => $lastOut?->check_out_at?->format('h:i A'),
            'total_minutes' => $totalMins,
            'working_hours' => $totalMins
                ? sprintf('%dh %02dm', intdiv($totalMins, 60), $totalMins % 60)
                : null,
            'punch_count'   => (int) $punchCount,
            'status'        => $status,
        ];
    }

    private function validDate(string $value): bool
    {
        try {
            Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d') === $value;

            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
