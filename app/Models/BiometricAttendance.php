<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiometricAttendance extends Model
{
    protected $fillable = [
        'employee_code', 'device_id', 'punch_time',
        'event_type', 'verify_type', 'session_id',
        'punch_direction',
    ];

    protected $casts = [
        'punch_time' => 'datetime',
    ];

    protected $appends = ['verify_type_label', 'verify_icon'];

    public function device()
    {
        return $this->belongsTo(BiometricDevice::class, 'device_id');
    }

    /**
     * Human-readable biometric method.
     *
     * eSSL AiFace Magnum verify codes:
     *   1   → Fingerprint
     *   15  → RFID Card
     *   255 → Face Recognition
     *   10  → Palm Recognition (some eSSL models)
     */
    public function getVerifyTypeLabelAttribute(): string
    {
        return match ((int) $this->verify_type) {
            1        => 'Fingerprint',
            2        => 'Face Recognition',
            3        => 'Password',
            4        => 'Card',
            10       => 'Palm Recognition',
            15       => 'RFID Card',
            255      => 'Face Recognition',
            default  => 'Biometric',
        };
    }

    // Returns icon key: face | card | fingerprint | palm | password | biometric
    public function getVerifyIconAttribute(): string
    {
        return match ((int) $this->verify_type) {
            1        => 'fingerprint',
            2, 255   => 'face',
            3        => 'password',
            4, 15    => 'card',
            10       => 'palm',
            default  => 'biometric',
        };
    }
}
