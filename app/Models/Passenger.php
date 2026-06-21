<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Passenger extends Model
{
    protected $fillable = [
        'booking_id',
        'sex',
        'date_of_birth',
        'user_phone_number',
        'contact_email_to',
        'contact_email_cc',
        'last_name',
        'first_name',
        'nationality',
        'optional_company_name',
        'referred_by_name',
        'contact_method',
        'survey_channel',
        'add_ons',
        'passport_number',
        'passport_expiry_date',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'passport_expiry_date' => 'date',
        'add_ons' => 'array',
        'contact_method' => 'integer',
        'survey_channel' => 'integer',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
