<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('use_immigration_fast_track')->default(false)->after('arrival_checked_baggage_availability');
            $table->boolean('tarmac_pickup')->default(false)->after('use_immigration_fast_track');
            $table->unsignedTinyInteger('pickup_service')->default(0)->after('tarmac_pickup');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['use_immigration_fast_track', 'tarmac_pickup', 'pickup_service']);
        });
    }
};
