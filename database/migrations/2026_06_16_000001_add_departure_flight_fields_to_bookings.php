<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('departure_flight_reservation_code', 20)->nullable()->after('departure_airport_code');
            $table->string('departure_flight_number', 20)->nullable()->after('departure_flight_reservation_code');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['departure_flight_reservation_code', 'departure_flight_number']);
        });
    }
};
