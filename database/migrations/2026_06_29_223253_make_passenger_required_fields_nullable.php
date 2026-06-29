<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('passengers', function (Blueprint $table) {
            $table->string('sex')->nullable()->change();
            $table->date('date_of_birth')->nullable()->change();
            $table->string('user_phone_number', 20)->nullable()->change();
            $table->string('contact_email_to')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('passengers', function (Blueprint $table) {
            $table->string('contact_email_to')->nullable(false)->change();
            $table->string('user_phone_number', 20)->nullable(false)->change();
            $table->date('date_of_birth')->nullable(false)->change();
            $table->string('sex')->nullable(false)->change();
        });
    }
};
