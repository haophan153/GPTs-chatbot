<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiCallLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'booking_code',
        'endpoint',
        'method',
        'request_body',
        'response_status',
        'response_body',
        'ip_address',
        'user_agent',
        'called_at',
    ];

    protected $casts = [
        'request_body' => 'array',
        'response_body' => 'array',
        'called_at' => 'datetime',
    ];
}
