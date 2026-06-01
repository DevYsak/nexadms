<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiometricAttendance extends Model
{
    protected $fillable = [
        'employee_code', 'device_id', 'punch_time', 'event_type', 'verify_type', 'session_id',
    ];

    protected $casts = [
        'punch_time' => 'datetime',
    ];

    protected $appends = ['verify_type_label'];

    public function device()
    {
        return $this->belongsTo(BiometricDevice::class, 'device_id');
    }

    public function getVerifyTypeLabelAttribute(): string
    {
        return match ((int) $this->verify_type) {
            1  => 'Fingerprint',
            3  => 'Password',
            4  => 'Card',
            15 => 'Face',
            default => 'Unknown',
        };
    }
}
