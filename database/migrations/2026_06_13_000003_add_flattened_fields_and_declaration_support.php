<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // 1. needs_declaration_support — SGN online declaration support
            $table->tinyInteger('needs_declaration_support')->default(0)->after('use_departure_fast_track');

            // 2. Arrival flattened fields
            $table->string('arrival_flight_reservation_code')->nullable()->after('needs_declaration_support');
            $table->string('arrival_flight_number')->nullable()->after('arrival_flight_reservation_code');
            $table->date('arrival_date')->nullable()->after('arrival_flight_number');
            $table->time('arrival_time')->nullable()->after('arrival_date');
            $table->string('arrival_phone_number')->nullable()->after('arrival_time');
            $table->text('arrival_request')->nullable()->after('arrival_phone_number');
            $table->enum('arrival_class_documents', ['economy', 'business'])->nullable()->after('arrival_request');
            $table->enum('arrival_checked_baggage_availability', ['available', 'not_available', 'undecided'])->nullable()->after('arrival_class_documents');

            // 3. Departure flattened fields
            $table->string('departure_flight_reservation_code')->nullable()->after('departure_fast_track_price');
            $table->string('departure_flight_number')->nullable()->after('departure_flight_reservation_code');
            $table->date('departure_date')->nullable()->after('departure_flight_number');
            $table->string('departure_phone_number')->nullable()->after('departure_date');
            $table->text('departure_request')->nullable()->after('departure_phone_number');
            $table->enum('departure_class_documents', ['economy', 'business'])->nullable()->after('departure_request');
            $table->enum('departure_checked_baggage_availability', ['available', 'not_available', 'undecided'])->nullable()->after('departure_class_documents');
            $table->string('departure_seating_preferences', 2)->nullable()->after('departure_checked_baggage_availability');
        });

        // Add needs_declaration_support to flight_details
        Schema::table('flight_details', function (Blueprint $table) {
            $table->tinyInteger('needs_declaration_support')->default(0)->after('tarmac_pickup');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'needs_declaration_support',
                'arrival_flight_reservation_code',
                'arrival_flight_number',
                'arrival_date',
                'arrival_time',
                'arrival_phone_number',
                'arrival_request',
                'arrival_class_documents',
                'arrival_checked_baggage_availability',
                'departure_flight_reservation_code',
                'departure_flight_number',
                'departure_date',
                'departure_phone_number',
                'departure_request',
                'departure_class_documents',
                'departure_checked_baggage_availability',
                'departure_seating_preferences',
            ]);
        });

        Schema::table('flight_details', function (Blueprint $table) {
            $table->dropColumn('needs_declaration_support');
        });
    }
};
