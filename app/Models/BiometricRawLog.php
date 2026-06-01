<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiometricRawLog extends Model
{
    protected $fillable = [
        'serial_number', 'log_type', 'body', 'records_parsed', 'received_at',
    ];

    protected $casts = [
        'received_at'    => 'datetime',
        'records_parsed' => 'integer',
    ];
}
