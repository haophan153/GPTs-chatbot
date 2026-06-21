<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->string('gender');
            $table->date('date_of_birth');
            $table->string('phone', 20);
            $table->string('email');
            $table->string('last_name');
            $table->string('first_name');
            $table->string('nationality');
            $table->string('passport_number');
            $table->date('passport_expiry');
            $table->integer('passenger_number')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfColumns(['passengers']);
    }
};
