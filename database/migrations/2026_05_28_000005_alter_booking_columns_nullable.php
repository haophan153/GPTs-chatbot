<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('service_package')->nullable()->change();
            $table->decimal('package_price', 10, 2)->nullable()->change();
            $table->decimal('subtotal', 10, 2)->nullable()->change();
            $table->decimal('vat_amount', 10, 2)->nullable()->change();
            $table->decimal('total_amount', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('service_package')->change();
            $table->decimal('package_price', 10, 2)->change();
            $table->decimal('subtotal', 10, 2)->change();
            $table->decimal('vat_amount', 10, 2)->change();
            $table->decimal('total_amount', 10, 2)->change();
        });
    }
};
