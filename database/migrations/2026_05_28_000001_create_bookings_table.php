<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique();
            $table->enum('status', ['draft', 'pending', 'confirmed', 'paid', 'cancelled'])->default('draft');
            $table->string('service_type');
            $table->string('airport_code', 3);
            $table->string('service_package');
            $table->decimal('package_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('vat_amount', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_method')->nullable();
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->text('options')->nullable();
            $table->string('contact_channel')->nullable();
            $table->string('referral_source')->nullable();
            $table->string('company_receipt')->nullable();
            $table->text('free_consultation')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfColumns(['bookings']);
    }
};
