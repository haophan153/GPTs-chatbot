<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 20)->nullable()->index();
            $table->string('endpoint', 100);
            $table->string('method', 10);
            $table->json('request_body')->nullable();
            $table->integer('response_status')->nullable();
            $table->json('response_body')->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('called_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_call_logs');
    }
};
