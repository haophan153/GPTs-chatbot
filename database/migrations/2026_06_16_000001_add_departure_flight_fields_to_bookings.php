<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            if (!Schema::hasColumn('bookings', 'departure_flight_reservation_code')) {
                Schema::table('bookings', function (Blueprint $table) {
                    $table->string('departure_flight_reservation_code')->nullable()->after('departure_airport_code');
                });
            }
        } catch (\Throwable $e) {
            // Column may already exist
        }

        try {
            if (!Schema::hasColumn('bookings', 'departure_flight_number')) {
                Schema::table('bookings', function (Blueprint $table) {
                    $table->string('departure_flight_number')->nullable()->after('departure_flight_reservation_code');
                });
            }
        } catch (\Throwable $e) {
            // Column may already exist
        }
    }

    public function down(): void
    {
        try {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn(['departure_flight_reservation_code', 'departure_flight_number']);
            });
        } catch (\Exception $e) {
        }
    }
};