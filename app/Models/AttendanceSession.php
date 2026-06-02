<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    protected $fillable = [
        'employee_code', 'session_date', 'session_index',
        'check_in_at', 'check_out_at', 'duration_minutes',
        'status', 'admin_note', 'corrected_by', 'corrected_at', 'is_overnight',
    ];

    protected $casts = [
        'check_in_at'   => 'datetime',
        'check_out_at'  => 'datetime',
        'corrected_at'  => 'datetime',
        'is_overnight'  => 'boolean',
    ];

    public static function dailySummaryFor(array $codes, string $date): array
    {
        return static::whereIn('employee_code', $codes)
            ->where('session_date', $date)
            ->get()
            ->groupBy('employee_code')
            ->map(function ($sessions) {
                $sorted        = $sessions->sortBy('session_index');
                $first         = $sorted->first();
                $lastCompleted = $sorted->filter(fn ($s) => $s->check_out_at !== null)->last();
                $openSession   = $sorted->filter(fn ($s) => $s->check_out_at === null)->first();
                $totalMins     = $sessions->sum('duration_minutes');

                // Status: in_office only when the LAST session is still open.
                // If the last session is completed → employee has checked out.
                $lastSession = $sorted->last();
                if ($lastSession->check_out_at !== null) {
                    $status = 'checked_out';
                } elseif ($lastSession->check_in_at !== null) {
                    $status = 'in_office';
                } else {
                    $status = 'absent';
                }

                return [
                    'first_in'      => $first->check_in_at?->format('h:i A'),
                    'last_out'      => $lastCompleted?->check_out_at?->format('h:i A'),
                    'working_hours' => $totalMins ? sprintf('%dh %02dm', intdiv($totalMins, 60), $totalMins % 60) : null,
                    'status'        => $status,
                ];
            })
            ->toArray();
    }

    public function getDurationHumanAttribute(): ?string
    {
        if (! $this->duration_minutes) return null;
        return sprintf('%dh %dm', intdiv($this->duration_minutes, 60), $this->duration_minutes % 60);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'present'         => 'Present',
            'in_office'       => 'In Office',
            'absent'          => 'Absent',
            'admin_corrected' => 'Corrected',
            default           => ucfirst($this->status ?? 'Unknown'),
        };
    }
}
