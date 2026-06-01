<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiometricDevice extends Model
{
    protected $fillable = [
        'serial_number', 'name', 'firmware_version', 'pushver', 'last_activity_at',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
    ];

    public function attendances()
    {
        return $this->hasMany(BiometricAttendance::class, 'device_id');
    }
}
