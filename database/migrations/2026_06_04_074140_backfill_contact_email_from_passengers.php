<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            UPDATE bookings
            SET contact_email = (
                SELECT email FROM passengers
                WHERE passengers.booking_id = bookings.id AND email IS NOT NULL AND email != ''
                LIMIT 1
            ),
            contact_phone = (
                SELECT phone FROM passengers
                WHERE passengers.booking_id = bookings.id AND phone IS NOT NULL AND phone != ''
                LIMIT 1
            )
            WHERE contact_email IS NULL OR contact_email = ''
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
