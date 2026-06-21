<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = [
        'booking_code',
        'booking_type',
        'use_departure_fast_track',
        'needs_declaration_support',
        'arrival_airport',
        'departure_airport_code',
        'entry_fast_track_option',
        'departure_fast_track_option',
        'entry_fast_track_price',
        'departure_fast_track_price',
        // Flattened arrival fields
        'arrival_flight_reservation_code',
        'arrival_flight_number',
        'arrival_date',
        'arrival_time',
        'arrival_phone_number',
        'arrival_request',
        'arrival_class_documents',
        'arrival_checked_baggage_availability',
        // Immigration addons (flattened from flight_details)
        'use_immigration_fast_track',
        'tarmac_pickup',
        'pickup_service',
        'pickup_time',
        // Flattened departure fields
        'departure_date',
        'departure_phone_number',
        'departure_request',
        'departure_class_documents',
        'departure_checked_baggage_availability',
        'departure_seating_preferences',
        // Departure flight info
        'departure_flight_reservation_code',
        'departure_flight_number',
        // Pricing fields
        'subtotal',
        'preliminary_calculation',
        'coupon_discount_amount',
        'two_way_discount',
        'night_surcharge_value',
        'tax',
        'total',
        'payment_method',
        'options',
        'user_phone_number',
        'contact_email_to',
        'contact_email_cc',
    ];

    protected $casts = [
        'options' => 'array',
        'entry_fast_track_price' => 'decimal:2',
        'departure_fast_track_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'preliminary_calculation' => 'decimal:2',
        'coupon_discount_amount' => 'decimal:2',
        'two_way_discount' => 'decimal:2',
        'night_surcharge_value' => 'decimal:2',
        'use_departure_fast_track' => 'boolean',
        'needs_declaration_support' => 'boolean',
        'use_immigration_fast_track' => 'boolean',
        'tarmac_pickup' => 'boolean',
        'pickup_service' => 'integer',
        'arrival_date' => 'date',
        'departure_date' => 'date',
        'pickup_time' => 'string',
    ];

    public function passengers(): HasMany
    {
        return $this->hasMany(Passenger::class);
    }

    public static function generateBookingCode(): string
    {
        return 'VJP-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 8));
    }
}
