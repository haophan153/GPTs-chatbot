<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'departure_flight_reservation_code',
                'company_receipt',
                'cancelled_at',
                'cancellation_reason',
            ]);
        });

        Schema::table('passengers', function (Blueprint $table) {
            $table->dropColumn('passenger_number');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('status', ['draft', 'pending', 'confirmed', 'paid', 'cancelled'])->default('draft')->after('booking_code');
            $table->string('departure_flight_reservation_code')->nullable()->after('departure_fast_track_price');
            $table->string('company_receipt')->nullable()->after('options');
            $table->timestamp('cancelled_at')->nullable()->after('contact_email_cc');
            $table->string('cancellation_reason')->nullable()->after('cancelled_at');
        });

        Schema::table('passengers', function (Blueprint $table) {
            $table->integer('passenger_number')->default(1)->after('booking_id');
        });
    }
};
