<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Passenger;
use App\Services\BookingExternalApiService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class TestExternalApiSeeder extends Seeder
{
    public function run(): void
    {
        // Tạo booking giống hệt mẫu JSON bạn gửi
        $booking = Booking::create([
            'booking_code'                          => Booking::generateBookingCode(),
            'booking_type'                          => 'departure',
            'use_departure_fast_track'              => true,
            'needs_declaration_support'             => true,
            'arrival_airport'                       => '0',
            'departure_airport_code'                => '0',
            'entry_fast_track_option'               => '0',
            'departure_fast_track_option'           => '4',
            'entry_fast_track_price'                => '0.00',
            'departure_fast_track_price'            => '35.00',
            'arrival_flight_reservation_code'       => null,
            'arrival_flight_number'                 => null,
            'arrival_date'                          => null,
            'arrival_time'                          => null,
            'arrival_phone_number'                  => null,
            'arrival_request'                       => null,
            'arrival_class_documents'               => null,
            'arrival_checked_baggage_availability'  => null,
            'use_immigration_fast_track'            => false,
            'tarmac_pickup'                         => false,
            'pickup_service'                        => 0,
            'pickup_time'                           => null,
            'departure_date'                        => '2027-09-12',
            'departure_phone_number'                => '+84898133490',
            'departure_request'                     => null,
            'departure_class_documents'             => 'economy',
            'departure_checked_baggage_availability'=> 'available',
            'departure_seating_preferences'         => null,
            'departure_flight_reservation_code'     => 'KJSHDSLJK',
            'departure_flight_number'              => 'JI232',
            'subtotal'                              => '35.00',
            'preliminary_calculation'               => '35.00',
            'two_way_discount'                      => '0.00',
            'coupon_discount_amount'                => '0.00',
            'night_surcharge_value'                 => '0.00',
            'tax'                                   => '2.80',
            'total'                                 => '37.80',
            'payment_method'                        => null,
            'user_phone_number'                     => '+84898133490',
            'contact_email_to'                      => 'kiemtra@gmail.com',
            'contact_email_cc'                      => 'haophan153204@gmail.com',
        ]);

        // Hành khách đầu tiên (sẽ được merge vào payload gửi API)
        Passenger::create([
            'booking_id'                => $booking->id,
            'sex'                       => '0',
            'date_of_birth'             => '2004-09-12',
            'user_phone_number'         => '+84898133490',
            'contact_email_to'          => 'kiemtra@gmail.com',
            'contact_email_cc'          => 'haophan153204@gmail.com',
            'last_name'                 => 'Phan',
            'first_name'                => 'ANh',
            'nationality'               => 'VietNam',
            'passport_number'           => 'KJSHDKSJ23',
            'passport_expiry_date'      => '2029-09-12',
            'optional_company_name'     => null,
            'referred_by_name'         => null,
            'contact_method'           => 0,
            'survey_channel'           => 0,
            'add_ons'                  => [],
        ]);

        // Gọi API external
        $service = app(BookingExternalApiService::class);
        $service->setUrl(config('services.booking_external_api.url') ?? env('WEB_BOOKING_API_URL'));

        $apiKey = config('services.booking_external_api.api_key') ?? env('WEB_BOOKING_API_KEY');
        if ($apiKey) {
            $service->setApiKey($apiKey);
        }

        $this->command->info('Booking code: ' . $booking->booking_code);
        $this->command->info('Calling external API...');

        $result = $service->finalize($booking);

        $this->command->info('Status: ' . $result['status']);
        $this->command->info('Success: ' . ($result['success'] ? 'YES' : 'NO'));
        $this->command->info('Response body:');
        $this->command->line(json_encode($result['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
