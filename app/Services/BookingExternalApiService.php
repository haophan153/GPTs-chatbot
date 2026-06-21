<?php

namespace App\Services;

use App\Models\ApiCallLog;
use App\Models\Booking;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookingExternalApiService
{
    private string $url;
    private ?string $apiKey = null;

    public function setUrl(string $url): void
    {
        $this->url = rtrim($url, '/');
    }

    public function setApiKey(?string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function finalize(Booking $booking): array
    {
        $payload = $this->buildPayload($booking);
        $request = $this->buildRequest();

        $startedAt = now();
        $requestBody = $payload;

        try {
            $response = $request->post($this->url, $payload);
            $status = $response->status();
            $body = $response->json() ?? [];

            $this->log($booking, 'POST', $this->url, $requestBody, $status, $body, $startedAt);

            return [
                'success' => $response->successful(),
                'status' => $status,
                'body' => $body,
            ];
        } catch (\Throwable $e) {
            $body = [
                'success' => false,
                'message' => $e->getMessage(),
            ];

            $this->log($booking, 'POST', $this->url, $requestBody, 0, $body, $startedAt);

            return [
                'success' => false,
                'status' => 0,
                'body' => $body,
            ];
        }
    }

    private function buildPayload(Booking $booking): array
    {
        $data = $this->buildStepResponse($booking);

        $passenger = $booking->passengers->first();

        $payload = [
            'booking_type'                          => $data['booking_type'],
            'use_departure_fast_track'              => $data['use_departure_fast_track'],
            'needs_declaration_support'             => $data['needs_declaration_support'],
            'arrival_airport'                       => $data['arrival_airport'],
            'departure_airport_code'                => $data['departure_airport_code'],
            'entry_fast_track_option'               => $data['entry_fast_track_option'],
            'departure_fast_track_option'           => $data['departure_fast_track_option'],
            'entry_fast_track_price'                => $data['entry_fast_track_price'],
            'departure_fast_track_price'            => $data['departure_fast_track_price'],
            'arrival_flight_reservation_code'       => $data['arrival_flight_reservation_code'],
            'arrival_flight_number'                 => $data['arrival_flight_number'],
            'arrival_date'                          => $data['arrival_date'],
            'arrival_time'                          => $data['arrival_time'],
            'arrival_phone_number'                  => $data['arrival_phone_number'],
            'arrival_request'                       => $data['arrival_request'],
            'arrival_class_documents'               => $data['arrival_class_documents'],
            'arrival_checked_baggage_availability'  => $data['arrival_checked_baggage_availability'],
            'use_immigration_fast_track'            => $data['use_immigration_fast_track'],
            'tarmac_pickup'                         => $data['tarmac_pickup'],
            'pickup_service'                        => $data['pickup_service'],
            'pickup_time'                           => $data['pickup_time'],
            'departure_date'                        => $data['departure_date'],
            'departure_phone_number'                => $data['departure_phone_number'],
            'departure_request'                     => $data['departure_request'],
            'departure_class_documents'             => $data['departure_class_documents'],
            'departure_checked_baggage_availability'=> $data['departure_checked_baggage_availability'],
            'departure_seating_preferences'         => $data['departure_seating_preferences'],
            'departure_flight_reservation_code'     => $data['departure_flight_reservation_code'],
            'departure_flight_number'               => $data['departure_flight_number'],
            'options'                               => $data['options'],
            'subtotal'                              => $data['subtotal'],
            'preliminary_calculation'               => $data['preliminary_calculation'],
            'two_way_discount'                      => $data['two_way_discount'],
            'coupon_discount_amount'                => $data['coupon_discount_amount'],
            'night_surcharge_value'                 => $data['night_surcharge_value'],
            'tax'                                   => $data['tax'],
            'total'                                 => $data['total'],
            'payment_method'                        => $data['payment_method'],
            'user_phone_number'                     => $data['user_phone_number'],
            'contact_email_to'                      => $data['contact_email_to'],
            'contact_email_cc'                      => $data['contact_email_cc'],
        ];

        if ($passenger) {
            $payload['sex']                   = $passenger->sex;
            $payload['date_of_birth']         = optional($passenger->date_of_birth)->toIso8601String();
            $payload['last_name']             = $passenger->last_name;
            $payload['first_name']            = $passenger->first_name;
            $payload['nationality']           = $passenger->nationality;
            $payload['passport_number']       = $passenger->passport_number;
            $payload['passport_expiry_date']  = optional($passenger->passport_expiry_date)->toIso8601String();
            $payload['user_phone_number']     = $passenger->user_phone_number ?? $data['user_phone_number'];
            $payload['optional_company_name'] = $passenger->optional_company_name;
            $payload['referred_by_name']      = $passenger->referred_by_name;
            $payload['contact_method']        = $passenger->contact_method;
            $payload['survey_channel']        = $passenger->survey_channel;
            $payload['add_ons']               = $passenger->add_ons ?? [];
        }

        // arrival_time: bỏ giây, chỉ giữ H:i
        if (isset($payload['arrival_time'])) {
            $payload['arrival_time'] = substr($payload['arrival_time'], 0, 5);
        }

        // checked_baggage_availability: "not_available" => "0", "available" => "1", "undecided" => "2"
        $baggageMap = ['not_available' => '0', 'available' => '1', 'undecided' => '2'];
        if (isset($payload['arrival_checked_baggage_availability'])) {
            $payload['arrival_checked_baggage_availability'] = $baggageMap[$payload['arrival_checked_baggage_availability']]
                ?? (in_array($payload['arrival_checked_baggage_availability'], ['0','1','2']) ? $payload['arrival_checked_baggage_availability'] : '0');
        }

        // arrival_class_documents: "economy" => "0", "business" => "1"
        $classMap = ['economy' => '0', 'business' => '1'];
        if (isset($payload['arrival_class_documents'])) {
            $payload['arrival_class_documents'] = $classMap[$payload['arrival_class_documents']]
                ?? (in_array($payload['arrival_class_documents'], ['0','1']) ? $payload['arrival_class_documents'] : '0');
        }

        // departure_class_documents: "economy" => "0", "business" => "1"
        if (isset($payload['departure_class_documents'])) {
            $payload['departure_class_documents'] = $classMap[$payload['departure_class_documents']]
                ?? (in_array($payload['departure_class_documents'], ['0','1']) ? $payload['departure_class_documents'] : '0');
        }

        // checked_baggage_availability: "not_available" => "0", "available" => "1", "undecided" => "2"
        if (isset($payload['departure_checked_baggage_availability'])) {
            $payload['departure_checked_baggage_availability'] = $baggageMap[$payload['departure_checked_baggage_availability']]
                ?? (in_array($payload['departure_checked_baggage_availability'], ['0','1','2']) ? $payload['departure_checked_baggage_availability'] : '0');
        }

        // Convert booleans sang string 'true'/'false'
        $payload['use_immigration_fast_track'] = ($payload['use_immigration_fast_track'] ?? false) ? 'true' : 'false';
        $payload['tarmac_pickup']              = ($payload['tarmac_pickup'] ?? false) ? 'true' : 'false';
        $payload['use_departure_fast_track']   = (bool) ($payload['use_departure_fast_track'] ?? false);
        $payload['needs_declaration_support']  = (bool) ($payload['needs_declaration_support'] ?? false);

        // Convert integers sang string
        $intFields = [
            'entry_fast_track_option', 'departure_fast_track_option',
            'contact_method', 'survey_channel', 'pickup_service', 'sex',
        ];
        foreach ($intFields as $field) {
            if (isset($payload[$field]) && $payload[$field] !== null) {
                $payload[$field] = (string) $payload[$field];
            }
        }

        return $payload;
    }

    private function buildRequest(): PendingRequest
    {
        $request = Http::timeout(30)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);

        if ($this->apiKey) {
            $request = $request->withToken($this->apiKey);
        }

        return $request;
    }

    private function log(
        Booking $booking,
        string $method,
        string $endpoint,
        array $requestBody,
        int $responseStatus,
        array $responseBody,
        $startedAt,
    ): void {
        try {
            ApiCallLog::create([
                'booking_code'    => $booking->booking_code,
                'endpoint'        => $endpoint,
                'method'          => $method,
                'request_body'    => $requestBody,
                'response_status' => $responseStatus,
                'response_body'   => $responseBody,
                'ip_address'      => request()->ip(),
                'user_agent'      => request()->userAgent() ?? 'internal',
                'called_at'       => $startedAt,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to log external API call', [
                'booking_code' => $booking->booking_code ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildStepResponse(Booking $booking): array
    {
        $booking->load('passengers');

        $collected = [];
        if ($booking->booking_type)               $collected[] = 'booking_type';
        if ($booking->arrival_airport)             $collected[] = 'arrival_airport';
        if ($booking->entry_fast_track_option)    $collected[] = 'entry_fast_track_option';
        if ($booking->entry_fast_track_price)     $collected[] = 'entry_fast_track_price';
        if (!$booking->passengers->isEmpty())      $collected[] = 'passengers';
        if ($booking->payment_method)              $collected[] = 'payment_method';

        return [
            'booking_code'                          => $booking->booking_code,
            'booking_type'                          => $booking->booking_type,
            'use_departure_fast_track'              => $booking->use_departure_fast_track,
            'needs_declaration_support'             => $booking->needs_declaration_support,
            'arrival_airport'                       => $booking->arrival_airport,
            'departure_airport_code'                => $booking->departure_airport_code,
            'entry_fast_track_option'               => $booking->entry_fast_track_option,
            'departure_fast_track_option'           => $booking->departure_fast_track_option,
            'entry_fast_track_price'                => $booking->entry_fast_track_price,
            'departure_fast_track_price'            => $booking->departure_fast_track_price,
            'arrival_flight_reservation_code'       => $booking->arrival_flight_reservation_code,
            'arrival_flight_number'                 => $booking->arrival_flight_number,
            'arrival_date'                          => $booking->arrival_date,
            'arrival_time'                          => $booking->arrival_time,
            'arrival_phone_number'                  => $booking->arrival_phone_number,
            'arrival_request'                       => $booking->arrival_request,
            'arrival_class_documents'               => $booking->arrival_class_documents,
            'arrival_checked_baggage_availability'  => $booking->arrival_checked_baggage_availability,
            'use_immigration_fast_track'            => $booking->use_immigration_fast_track,
            'tarmac_pickup'                         => $booking->tarmac_pickup,
            'pickup_service'                        => $booking->pickup_service,
            'pickup_time'                           => $booking->pickup_time,
            'departure_date'                        => $booking->departure_date,
            'departure_phone_number'                => $booking->departure_phone_number,
            'departure_request'                     => $booking->departure_request,
            'departure_class_documents'             => $booking->departure_class_documents,
            'departure_checked_baggage_availability'=> $booking->departure_checked_baggage_availability,
            'departure_seating_preferences'         => $booking->departure_seating_preferences,
            'departure_flight_reservation_code'     => $booking->departure_flight_reservation_code,
            'departure_flight_number'               => $booking->departure_flight_number,
            'options'                               => $booking->options,
            'subtotal'                              => $booking->subtotal,
            'preliminary_calculation'               => $booking->preliminary_calculation,
            'two_way_discount'                      => $booking->two_way_discount,
            'coupon_discount_amount'                => $booking->coupon_discount_amount,
            'night_surcharge_value'                 => $booking->night_surcharge_value,
            'tax'                                   => $booking->tax,
            'total'                                 => $booking->total,
            'payment_method'                        => $booking->payment_method,
            'user_phone_number'                     => $booking->user_phone_number,
            'contact_email_to'                      => $booking->contact_email_to,
            'contact_email_cc'                      => $booking->contact_email_cc,
            'passengers'                            => $booking->passengers,
            'collected_fields'                      => $collected,
            'missing_fields'                        => [],
            'next_step'                             => 'confirmed',
            'created_at'                            => optional($booking->created_at)->toIso8601String(),
            'updated_at'                            => optional($booking->updated_at)->toIso8601String(),
        ];
    }
}
