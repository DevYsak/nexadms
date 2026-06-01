<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'employee_code',
        'name',
        'department',
        'designation',
        'email',
        'phone',
        'photo',
        'is_active',
        'shift_start',
        'shift_end',
        'late_threshold_minutes',
    ];

    protected $casts = [
        'is_active'               => 'boolean',
        'late_threshold_minutes'  => 'integer',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function attendances(): HasMany
    {
        return $this->hasMany(BiometricAttendance::class, 'employee_code', 'employee_code');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDepartment(Builder $query, string $dept): Builder
    {
        return $query->where('department', $dept);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Avatar initials (max 2 chars).
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', trim($this->name));
        return strtoupper(
            count($words) >= 2
                ? $words[0][0] . $words[1][0]
                : substr($words[0], 0, 2)
        );
    }

    /**
     * Deterministic avatar background color from employee code.
     */
    public function getAvatarColorAttribute(): string
    {
        $colors = [
            '#6366f1', '#8b5cf6', '#ec4899', '#f59e0b',
            '#10b981', '#3b82f6', '#ef4444', '#14b8a6',
            '#f97316', '#84cc16', '#06b6d4', '#a855f7',
        ];
        return $colors[(int) $this->employee_code % count($colors)];
    }

    /**
     * Attendance summary for a specific date.
     * Returns: first_in, last_out, punch_count, working_hours_string, status.
     */
    public function dailySummary(string $date, ?string $deviceSn = null): array
    {
        $query = $this->attendances()
            ->whereDate('punch_time', $date)
            ->orderBy('punch_time');

        if ($deviceSn) {
            $query->whereHas('device', fn ($q) => $q->where('serial_number', $deviceSn));
        }

        $punches = $query->with('device')->get();

        if ($punches->isEmpty()) {
            return [
                'punch_count'   => 0,
                'first_in'      => null,
                'last_out'      => null,
                'working_hours' => null,
                'status'        => 'absent',
                'device_sn'     => null,
                'verify_label'  => null,
            ];
        }

        $first = $punches->first();
        $last  = $punches->last();

        $firstIn = Carbon::parse($first->punch_time);
        $lastOut = $first->id !== $last->id ? Carbon::parse($last->punch_time) : null;

        $workingMins = $lastOut ? $firstIn->diffInMinutes($lastOut) : null;
        $workingHours = $workingMins !== null
            ? sprintf('%02dh %02dm', intdiv($workingMins, 60), $workingMins % 60)
            : null;

        // Status determination
        $shiftStart = Carbon::parse($date . ' ' . $this->shift_start);
        $lateLimit  = $shiftStart->copy()->addMinutes($this->late_threshold_minutes);
        $isLate     = $firstIn->gt($lateLimit);

        if ($lastOut !== null) {
            $status = $isLate ? 'late' : 'present';
        } else {
            $status = $isLate ? 'late' : 'in_office';
        }

        return [
            'punch_count'   => $punches->count(),
            'first_in'      => $firstIn->format('h:i A'),
            'last_out'      => $lastOut?->format('h:i A'),
            'working_hours' => $workingHours,
            'status'        => $status,
            'device_sn'     => $first->device?->serial_number ?? '-',
            'verify_label'  => $first->verify_type_label,
        ];
    }

    /**
     * All unique departments.
     */
    public static function departments(): array
    {
        return static::active()
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->toArray();
    }
}
