<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // === BOOKINGS TABLE: rename and add fields ===
        // Wrap in try-catch to be idempotent (safe to re-run)
        try {
            Schema::table('bookings', function (Blueprint $table) {
                // Rename service_type -> booking_type
                $table->renameColumn('service_type', 'booking_type');

                // Rename airport_code -> arrival_airport
                $table->renameColumn('airport_code', 'arrival_airport');

                // Rename service_package -> entry_fast_track_option
                $table->renameColumn('service_package', 'entry_fast_track_option');

                // Rename package_price -> entry_fast_track_price
                $table->renameColumn('package_price', 'entry_fast_track_price');

                // Rename total_amount -> total
                $table->renameColumn('total_amount', 'total');

                // Rename contact_email -> contact_email_to
                $table->renameColumn('contact_email', 'contact_email_to');

                // Rename contact_phone -> user_phone_number
                $table->renameColumn('contact_phone', 'user_phone_number');

                // Add new fields
                $table->tinyInteger('use_departure_fast_track')->default(0)->after('booking_type');
                $table->string('departure_airport_code', 3)->nullable()->after('arrival_airport');
                $table->string('departure_fast_track_option')->nullable()->after('entry_fast_track_option');
                $table->decimal('departure_fast_track_price', 10, 2)->nullable()->after('entry_fast_track_price');
                $table->string('contact_email_cc')->nullable()->after('contact_email_to');
                $table->decimal('preliminary_calculation', 10, 2)->nullable()->after('total');
                $table->decimal('coupon_discount_amount', 10, 2)->default(0)->after('preliminary_calculation');
                $table->decimal('two_way_discount', 10, 2)->default(0)->after('coupon_discount_amount');
                $table->decimal('night_surcharge_value', 10, 2)->default(0)->after('two_way_discount');
                $table->decimal('tax', 10, 2)->nullable()->after('night_surcharge_value');
                $table->string('payment_method')->nullable()->change();
            });
        } catch (\Exception $e) {
            // Some columns may already be renamed or removed by later migrations
        }

        // === PASSENGERS TABLE: rename and add fields ===
        try {
            Schema::table('passengers', function (Blueprint $table) {
                // Rename gender -> sex
                $table->renameColumn('gender', 'sex');

                // Rename phone -> user_phone_number
                $table->renameColumn('phone', 'user_phone_number');

                // Rename email -> contact_email_to
                $table->renameColumn('email', 'contact_email_to');

                // Rename passport_expiry -> passport_expiry_date
                $table->renameColumn('passport_expiry', 'passport_expiry_date');

                // Add new fields
                $table->string('contact_email_cc')->nullable()->after('contact_email_to');
                $table->string('optional_company_name')->nullable()->after('nationality');
                $table->string('referred_by_name')->nullable()->after('optional_company_name');
                $table->tinyInteger('contact_method')->nullable()->after('referred_by_name');
                $table->tinyInteger('survey_channel')->nullable()->after('contact_method');
                $table->json('add_ons')->nullable()->after('survey_channel');
            });
        } catch (\Exception $e) {
            // Some columns may already be renamed or removed
        }

        // === FLIGHT_DETAILS TABLE: rename and add fields, then split into 2 tables ===
        try {
            Schema::table('flight_details', function (Blueprint $table) {
                // Rename baggage -> checked_baggage_availability
                $table->renameColumn('baggage', 'checked_baggage_availability');

                // Rename vietnamese_contact_phone -> phone_number
                $table->renameColumn('vietnamese_contact_phone', 'phone_number');

                // Add arrival-specific fields
                $table->string('flight_reservation_code')->nullable()->after('booking_code');
                $table->string('class_documents')->nullable()->after('flight_time');
                $table->boolean('use_immigration_fast_track')->default(false)->after('class_documents');
                $table->boolean('tarmac_pickup')->default(false)->after('use_immigration_fast_track');
                $table->tinyInteger('pickup_service')->default(0)->after('tarmac_pickup');
                $table->string('request')->nullable()->after('seat_request');

                // Add departure-specific fields
                $table->time('pickup_time')->nullable()->after('request');
                $table->string('seating_preferences')->nullable()->after('pickup_time');
            });
        } catch (\Exception $e) {
            // flight_details table may have been dropped (flattened into bookings)
        }
    }

    public function down(): void
    {
        // === BOOKINGS TABLE ===
        try {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn([
                    'use_departure_fast_track',
                    'departure_airport_code',
                    'departure_fast_track_option',
                    'departure_fast_track_price',
                    'contact_email_cc',
                    'preliminary_calculation',
                    'coupon_discount_amount',
                    'two_way_discount',
                    'night_surcharge_value',
                    'tax',
                ]);

                $table->renameColumn('booking_type', 'service_type');
                $table->renameColumn('arrival_airport', 'airport_code');
                $table->renameColumn('entry_fast_track_option', 'service_package');
                $table->renameColumn('entry_fast_track_price', 'package_price');
                $table->renameColumn('total', 'total_amount');
                $table->renameColumn('contact_email_to', 'contact_email');
                $table->renameColumn('user_phone_number', 'contact_phone');
            });
        } catch (\Exception $e) {
        }

        // === PASSENGERS TABLE ===
        try {
            Schema::table('passengers', function (Blueprint $table) {
                $table->dropColumn([
                    'contact_email_cc',
                    'optional_company_name',
                    'referred_by_name',
                    'contact_method',
                    'survey_channel',
                    'add_ons',
                ]);

                $table->renameColumn('sex', 'gender');
                $table->renameColumn('user_phone_number', 'phone');
                $table->renameColumn('contact_email_to', 'email');
                $table->renameColumn('passport_expiry_date', 'passport_expiry');
            });
        } catch (\Exception $e) {
        }

        // === FLIGHT_DETAILS TABLE ===
        try {
            Schema::table('flight_details', function (Blueprint $table) {
                $table->dropColumn([
                    'flight_reservation_code',
                    'class_documents',
                    'use_immigration_fast_track',
                    'tarmac_pickup',
                    'pickup_service',
                    'request',
                    'pickup_time',
                    'seating_preferences',
                ]);

                $table->renameColumn('checked_baggage_availability', 'baggage');
                $table->renameColumn('phone_number', 'vietnamese_contact_phone');
            });
        } catch (\Exception $e) {
        }
    }
};