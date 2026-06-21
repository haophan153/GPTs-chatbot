<?php

namespace App\Http\Controllers;

use App\Models\ApiCallLog;
use App\Models\Booking;
use App\Models\BookingOtp;
use App\Models\Passenger;
use App\Mail\BookingCancellationOtp;
use App\Services\BookingExternalApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    public function init(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'booking_type'       => 'nullable|in:both,arrival,departure',
            'arrival_airport'   => 'nullable|in:0,1,2,3',
            'departure_airport'    => 'nullable|in:0,1,2,3',
            'user_phone_number' => 'nullable|string|max:20',
            'contact_email_to'  => 'nullable|email',
            'from_booking_code' => 'nullable|string',
            // Flattened arrival fields
            'arrival_flight_reservation_code'  => 'nullable|string|max:20',
            'arrival_flight_number'            => 'nullable|string|max:20',
            'arrival_date'                    => 'nullable|date',
            'arrival_time'                    => 'nullable|date_format:H:i',
            'arrival_phone_number'            => 'nullable|string|max:20',
            'arrival_request'                => 'nullable|string',
            'arrival_class_documents'         => 'nullable|in:economy,business',
            'arrival_checked_baggage_availability' => 'nullable|in:available,not_available,undecided',
            // Immigration addons
            'use_immigration_fast_track' => 'nullable|boolean',
            'tarmac_pickup'              => 'nullable|boolean',
            'pickup_service'             => 'nullable|integer|min:0|max:3',
            'pickup_time'                => 'nullable|date_format:H:i',
            // Flattened departure fields
            'departure_date'                  => 'nullable|date',
            'departure_phone_number'          => 'nullable|string|max:20',
            'departure_request'              => 'nullable|string',
            'departure_class_documents'      => 'nullable|in:economy,business',
            'departure_checked_baggage_availability' => 'nullable|in:available,not_available,undecided',
            'departure_seating_preferences'  => 'nullable|string|max:2',
            'departure_flight_reservation_code' => 'nullable|string|max:20',
            'departure_flight_number'           => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $bookingType      = $request->booking_type;
        $arrivalAirport   = $request->arrival_airport;
        $departureAirport = $request->departure_airport;
        $entryOption      = null;
        $departureOption  = null;
        $entryPrice       = 0;
        $departurePrice   = 0;
        $options          = null;

        if ($request->from_booking_code) {
            $oldBooking = Booking::where('booking_code', $request->from_booking_code)->first();
            if ($oldBooking) {
                $bookingType      = $bookingType      ?? $oldBooking->booking_type;
                $arrivalAirport   = $arrivalAirport   ?? $oldBooking->arrival_airport;
                $departureAirport = $departureAirport ?? $oldBooking->departure_airport_code;
                $entryOption     = $oldBooking->entry_fast_track_option;
                $departureOption  = $oldBooking->departure_fast_track_option;
                $entryPrice      = (float) $oldBooking->entry_fast_track_price;
                $departurePrice  = (float) ($oldBooking->departure_fast_track_price ?? 0);
                $options         = $oldBooking->options;
            }
        }

        if (is_null($bookingType) || is_null($arrivalAirport)) {
            return response()->json([
                'success' => false,
                'message' => 'booking_type and arrival_airport are required. If you are reusing a previous booking, provide from_booking_code to auto-fill these fields.',
            ], 422);
        }

        $booking = Booking::create([
            'booking_code'              => Booking::generateBookingCode(),
            'booking_type'              => $bookingType,
            'use_departure_fast_track' => ($bookingType === 'both') ? 1 : 0,
            'needs_declaration_support' => (int) ($request->needs_declaration_support ?? 0),
            'arrival_airport'           => $arrivalAirport,
            'departure_airport_code'    => $departureAirport,
            'entry_fast_track_option'   => $entryOption,
            'departure_fast_track_option' => $departureOption,
            'entry_fast_track_price'    => $entryPrice,
            'departure_fast_track_price' => $departurePrice,
            // Flattened arrival fields
            'arrival_flight_reservation_code'        => $request->arrival_flight_reservation_code,
            'arrival_flight_number'                  => $request->arrival_flight_number,
            'arrival_date'                          => $request->arrival_date,
            'arrival_time'                          => $request->arrival_time,
            'arrival_phone_number'                  => $request->arrival_phone_number,
            'arrival_request'                      => $request->arrival_request,
            'arrival_class_documents'              => $request->arrival_class_documents,
            'arrival_checked_baggage_availability' => $request->arrival_checked_baggage_availability,
            // Immigration addons
            'use_immigration_fast_track' => $request->use_immigration_fast_track ? 1 : 0,
            'tarmac_pickup'             => $request->tarmac_pickup ? 1 : 0,
            'pickup_service'            => (int) ($request->pickup_service ?? 0),
            'pickup_time'               => $request->pickup_time,
            // Flattened departure fields
            'departure_date'                        => $request->departure_date,
            'departure_phone_number'                => $request->departure_phone_number,
            'departure_request'                    => $request->departure_request,
            'departure_class_documents'            => $request->departure_class_documents,
            'departure_checked_baggage_availability' => $request->departure_checked_baggage_availability,
            'departure_seating_preferences'        => $request->departure_seating_preferences,
            'subtotal'                  => 0,
            'total'                     => 0,
            'user_phone_number'         => $request->user_phone_number,
            'contact_email_to'          => $request->contact_email_to,
            'options'                   => $options,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking draft created.',
            'data' => $this->buildStepResponse($booking),
        ], 201);
    }

    public function updateStep(Request $request, string $bookingCode): JsonResponse
    {
        $booking = Booking::with('passengers')
            ->where('booking_code', $bookingCode)
            ->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        $data = $request->all();

        $result = DB::transaction(function () use ($booking, $data) {
            // Booking-level fields
            $bookingFields = [
                'booking_type', 'use_departure_fast_track', 'needs_declaration_support',
                'arrival_airport', 'departure_airport_code',
                'entry_fast_track_option', 'departure_fast_track_option',
                'entry_fast_track_price', 'departure_fast_track_price',
                'payment_method',
                'user_phone_number', 'contact_email_to', 'contact_email_cc',
                'preliminary_calculation', 'coupon_discount_amount',
                'two_way_discount', 'night_surcharge_value',
                // Flattened arrival fields
                'arrival_flight_reservation_code', 'arrival_flight_number',
                'arrival_date', 'arrival_time', 'arrival_phone_number',
                'arrival_request', 'arrival_class_documents', 'arrival_checked_baggage_availability',
                // Immigration addons
                'use_immigration_fast_track', 'tarmac_pickup', 'pickup_service', 'pickup_time',
                // Flattened departure fields
                'departure_date', 'departure_phone_number',
                'departure_request', 'departure_class_documents',
                'departure_checked_baggage_availability', 'departure_seating_preferences',
                'departure_flight_reservation_code', 'departure_flight_number',
            ];

            foreach ($bookingFields as $field) {
                if (array_key_exists($field, $data)) {
                    if ($field === 'departure_seating_preferences') {
                        $booking->$field = $this->normalizeSeatingPreference($data[$field]);
                    } else {
                        $booking->$field = $data[$field];
                    }
                }
            }

            if (isset($data['options'])) {
                $booking->options = $data['options'];
            }

            // Passengers — replace all
            if (isset($data['passengers']) && is_array($data['passengers'])) {
                $booking->passengers()->delete();
                foreach ($data['passengers'] as $idx => $pax) {
                    $sexValue = $pax['sex'] ?? null;
                    if ($sexValue !== null) {
                        $sexValue = $this->normalizeSex($sexValue);
                    }

                    Passenger::create([
                        'booking_id'           => $booking->id,
                        'sex'                  => $sexValue,
                        'date_of_birth'        => $pax['date_of_birth']  ?? null,
                        'user_phone_number'    => $pax['user_phone_number'] ?? $pax['phone'] ?? null,
                        'contact_email_to'     => $pax['contact_email_to'] ?? $pax['email'] ?? null,
                        'last_name'           => $pax['last_name']  ?? null,
                        'first_name'          => $pax['first_name'] ?? null,
                        'nationality'         => $pax['nationality'] ?? null,
                        'passport_number'     => $pax['passport_number'] ?? null,
                        'passport_expiry_date' => $pax['passport_expiry_date'] ?? $pax['passport_expiry'] ?? null,
                        'contact_email_cc'    => $pax['contact_email_cc'] ?? null,
                        'optional_company_name' => $pax['optional_company_name'] ?? null,
                        'referred_by_name'    => $pax['referred_by_name'] ?? null,
                        'contact_method'      => isset($pax['contact_method']) ? (int) $pax['contact_method'] : null,
                        'survey_channel'      => isset($pax['survey_channel']) ? (int) $pax['survey_channel'] : null,
                        'add_ons'             => isset($pax['add_ons']) ? $pax['add_ons'] : null,
                    ]);
                }

                // Sync contact info from first passenger
                if (empty($booking->contact_email_to) && !empty($data['passengers'][0]['contact_email_to'])) {
                    $booking->contact_email_to = $data['passengers'][0]['contact_email_to'];
                }
                if (empty($booking->user_phone_number) && !empty($data['passengers'][0]['user_phone_number'])) {
                    $booking->user_phone_number = $data['passengers'][0]['user_phone_number'];
                }
            }

            $this->recalculatePricing($booking);
            $booking->save();

            return $booking->load('passengers');
        });

        return response()->json([
            'success' => true,
            'message' => 'Booking updated.',
            'data' => $this->buildStepResponse($result),
        ]);
    }

    public function confirm(Request $request, string $bookingCode): JsonResponse
    {
        $booking = Booking::with('passengers')
            ->where('booking_code', $bookingCode)
            ->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        $missingFields = $this->getMissingFields($booking);

        if (!empty($missingFields)) {
            return response()->json([
                'success' => false,
                'message' => 'Booking cannot be confirmed. Missing required fields.',
                'missing_fields' => $missingFields,
            ], 422);
        }

        $finalized = $this->finalizeWithExternalApi($booking);

        if (!$finalized['success']) {
            Log::error('External booking API failed', [
                'booking_code' => $booking->booking_code,
                'status' => $finalized['status'],
                'response' => $finalized['body'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Booking confirmed locally but failed to sync with external system. Please contact support.',
                'data' => $this->buildStepResponse($booking),
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking confirmed successfully.',
            'data' => $this->buildStepResponse($booking),
        ]);
    }

    private function finalizeWithExternalApi(Booking $booking): array
    {
        $url = config('services.booking_external_api.url') ?? env('WEB_BOOKING_API_URL');

        if (!$url) {
            return ['success' => true, 'status' => 200, 'body' => ['skipped' => true]];
        }

        $service = app(BookingExternalApiService::class);
        $service->setUrl($url);

        $apiKey = config('services.booking_external_api.api_key') ?? env('WEB_BOOKING_API_KEY');

        if ($apiKey) {
            $service->setApiKey($apiKey);
        }

        return $service->finalize($booking);
    }

    public function lookup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        if (!$request->phone && !$request->email) {
            return response()->json(['success' => false, 'message' => 'At least phone or email is required.'], 422);
        }

        $query = Booking::with('passengers')
            ->where(function ($q) use ($request) {
                if ($request->phone && $request->email) {
                    $q->where('user_phone_number', $request->phone)
                      ->orWhere('contact_email_to', $request->email)
                      ->orWhereHas('passengers', fn($p) => $p->where('contact_email_to', $request->email));
                } elseif ($request->phone) {
                    $q->where('user_phone_number', $request->phone);
                } else {
                    $q->where('contact_email_to', $request->email)
                      ->orWhereHas('passengers', fn($p) => $p->where('contact_email_to', $request->email));
                }
            });

        $bookings = $query->orderBy('created_at', 'desc')->get();

        if ($bookings->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No bookings found for this contact.',
                'is_returning_customer' => false,
            ], 404);
        }

        $latest = $bookings->first();

        return response()->json([
            'success' => true,
            'is_returning_customer' => true,
            'latest_booking' => [
                'booking_code'               => $latest->booking_code,
                'booking_type'               => $latest->booking_type,
                'use_departure_fast_track'   => $latest->use_departure_fast_track,
                'arrival_airport'            => $latest->arrival_airport,
                'departure_airport_code'      => $latest->departure_airport_code,
                'entry_fast_track_option'    => $latest->entry_fast_track_option,
                'departure_fast_track_option' => $latest->departure_fast_track_option,
                'entry_fast_track_price'     => $latest->entry_fast_track_price,
                'departure_fast_track_price'  => $latest->departure_fast_track_price,
                'options'                    => $latest->options,
                'passengers' => $latest->passengers->map(fn($p) => [
                    'sex'                 => $p->sex,
                    'first_name'          => $p->first_name,
                    'last_name'           => $p->last_name,
                    'passport_number'     => $p->passport_number,
                    'passport_expiry_date' => $p->passport_expiry_date,
                    'nationality'         => $p->nationality,
                ]),
            ],
            'all_bookings' => $bookings,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data         = $request->all();
        $passengers   = $data['passengers'] ?? [];

        $result = DB::transaction(function () use ($data, $passengers) {
            $bookingCode      = Booking::generateBookingCode();
            $entryPrice     = (float) ($data['entry_fast_track_price'] ?? 0);
            $departurePrice = (float) ($data['departure_fast_track_price'] ?? 0);

            $useImmigrationFastTrack = isset($data['use_immigration_fast_track']) && $data['use_immigration_fast_track'];
            $tarmacPickup           = isset($data['tarmac_pickup']) && $data['tarmac_pickup'];
            $immigrationAddon = $useImmigrationFastTrack ? 15.00 : 0.00;
            $tarmacAddon     = $tarmacPickup ? 60.00 : 0.00;
            $pickupValue     = 0.00;
            if (isset($data['pickup_service'])) {
                $ps = (int) $data['pickup_service'];
                if ($ps === 1)      $pickupValue = 20.00;
                elseif ($ps === 2)  $pickupValue = 25.00;
                elseif ($ps === 3)  $pickupValue = 50.00;
            }

            $subtotal = $entryPrice + $departurePrice
                      + $immigrationAddon
                      + $tarmacAddon
                      + $pickupValue;

            $twoWayDiscount    = (float) ($data['two_way_discount'] ?? 0);
            $couponDiscount    = (float) ($data['coupon_discount_amount'] ?? 0);
            $nightSurcharge    = (float) ($data['night_surcharge_value'] ?? 0);
            $preliminaryCalc   = $subtotal - $twoWayDiscount;
            $tax               = round($preliminaryCalc * 0.08, 2);
            $totalAmount       = round($preliminaryCalc + $tax + $nightSurcharge - $couponDiscount, 2);

            $booking = Booking::create([
                'booking_code'               => $bookingCode,
                'booking_type'               => $data['booking_type'],
                'use_departure_fast_track'   => $data['use_departure_fast_track'] ?? 0,
                'needs_declaration_support'  => isset($data['needs_declaration_support']) ? (int) $data['needs_declaration_support'] : 0,
                'arrival_airport'            => $data['arrival_airport'],
                'departure_airport_code'     => $data['departure_airport'] ?? null,
                'entry_fast_track_option'    => $data['entry_fast_track_option'],
                'departure_fast_track_option' => $data['departure_fast_track_option'] ?? null,
                'entry_fast_track_price'    => $entryPrice,
                'departure_fast_track_price' => $departurePrice,
                // Flattened arrival
                'arrival_flight_reservation_code'        => $data['arrival_flight_reservation_code'] ?? null,
                'arrival_flight_number'                  => $data['arrival_flight_number'] ?? null,
                'arrival_date'                          => $data['arrival_date'] ?? null,
                'arrival_time'                          => $data['arrival_time'] ?? null,
                'arrival_phone_number'                  => $data['arrival_phone_number'] ?? null,
                'arrival_request'                      => $data['arrival_request'] ?? null,
                'arrival_class_documents'              => $data['arrival_class_documents'] ?? null,
                'arrival_checked_baggage_availability' => $data['arrival_checked_baggage_availability'] ?? null,
                // Immigration addons
                'use_immigration_fast_track' => isset($data['use_immigration_fast_track']) && $data['use_immigration_fast_track'] ? 1 : 0,
                'tarmac_pickup'             => isset($data['tarmac_pickup']) && $data['tarmac_pickup'] ? 1 : 0,
                'pickup_service'            => (int) ($data['pickup_service'] ?? 0),
                // Flattened departure
                'departure_date'                        => $data['departure_date'] ?? null,
                'departure_phone_number'                => $data['departure_phone_number'] ?? null,
                'departure_request'                    => $data['departure_request'] ?? null,
                'departure_class_documents'            => $data['departure_class_documents'] ?? null,
                'departure_checked_baggage_availability' => $data['departure_checked_baggage_availability'] ?? null,
                'departure_seating_preferences'        => $data['departure_seating_preferences'] ?? null,
                'departure_flight_reservation_code'    => $data['departure_flight_reservation_code'] ?? null,
                'departure_flight_number'              => $data['departure_flight_number'] ?? null,
                // Pricing
                'subtotal'                   => $subtotal,
                'tax'                       => $tax,
                'total'                     => $totalAmount,
                'preliminary_calculation'   => $preliminaryCalc,
                'coupon_discount_amount'    => $couponDiscount,
                'two_way_discount'         => $twoWayDiscount,
                'night_surcharge_value'    => $nightSurcharge,
                'payment_method'             => $data['payment_method'] ?? null,
                'options'                    => $data['options'] ?? null,
                'user_phone_number'         => $data['user_phone_number'] ?? null,
                'contact_email_to'          => $data['contact_email_to'] ?? null,
                'contact_email_cc'          => $data['contact_email_cc'] ?? null,
            ]);

            foreach ($passengers as $pax) {
                Passenger::create([
                    'booking_id'           => $booking->id,
                    'sex'                  => $pax['sex'],
                    'date_of_birth'        => $pax['date_of_birth'],
                    'user_phone_number'    => $pax['user_phone_number'] ?? $pax['phone'] ?? null,
                    'contact_email_to'     => $pax['contact_email_to'] ?? $pax['email'] ?? null,
                    'last_name'           => $pax['last_name'],
                    'first_name'          => $pax['first_name'],
                    'nationality'         => $pax['nationality'],
                    'passport_number'     => $pax['passport_number'],
                    'passport_expiry_date' => $pax['passport_expiry_date'] ?? $pax['passport_expiry'] ?? null,
                    'contact_email_cc'    => $pax['contact_email_cc'] ?? null,
                    'optional_company_name' => $pax['optional_company_name'] ?? null,
                    'referred_by_name'    => $pax['referred_by_name'] ?? null,
                    'contact_method'      => isset($pax['contact_method']) ? (int) $pax['contact_method'] : null,
                    'survey_channel'      => isset($pax['survey_channel']) ? (int) $pax['survey_channel'] : null,
                    'add_ons'             => $pax['add_ons'] ?? null,
                ]);
            }

            return $booking->load('passengers');
        });

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully.',
            'data' => $result,
        ], 201);
    }

    public function show(string $bookingCode): JsonResponse
    {
        $booking = Booking::with('passengers')
            ->where('booking_code', $bookingCode)
            ->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $booking]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Booking::with('passengers');

        if ($request->has('airport_code')) {
            $query->where('arrival_airport', $request->airport_code);
        }
        if ($request->has('phone')) {
            $query->where('user_phone_number', $request->phone);
        }
        if ($request->has('email')) {
            $query->orWhere('contact_email_to', $request->email);
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json(['success' => true, 'data' => $bookings]);
    }

    public function update(Request $request, string $bookingCode): JsonResponse
    {
        $booking = Booking::where('booking_code', $bookingCode)->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        $data = $request->all();

        $result = DB::transaction(function () use ($booking, $data) {
            if (isset($data['payment_method']))   $booking->payment_method = $data['payment_method'];
            $booking->save();

            return $booking->load('passengers');
        });

        return response()->json([
            'success' => true,
            'message' => 'Booking updated successfully.',
            'data' => $result,
        ]);
    }

    public function destroy(string $bookingCode): JsonResponse
    {
        $booking = Booking::where('booking_code', $bookingCode)->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        $booking->passengers()->delete();
        $booking->delete();

        return response()->json(['success' => true, 'message' => 'Booking permanently deleted.']);
    }

    public function cancelWithVerification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'booking_code'    => 'required|string',
            'passport_number' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 'verified' => false,
                'message' => 'Validation failed.', 'errors' => $validator->errors(),
            ], 422);
        }

        $booking = Booking::with('passengers')
            ->where('booking_code', $request->booking_code)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false, 'verified' => false, 'cancelled' => false,
                'message' => 'Booking not found.',
            ], 404);
        }

        $normalized = strtoupper(trim($request->passport_number));
        $matched = $booking->passengers->contains(fn($p) => strtoupper(trim($p->passport_number)) === $normalized);

        if (!$matched) {
            return response()->json([
                'success' => false, 'verified' => false, 'cancelled' => false,
                'message' => 'Passport number does not match. Verification failed.',
            ]);
        }

        $booking->passengers()->delete();
        $booking->delete();

        return response()->json([
            'success' => true, 'verified' => true, 'cancelled' => true,
            'message' => 'Booking permanently deleted.',
        ]);
    }

    public function requestCancellationOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), ['booking_code' => 'required|string']);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $booking = Booking::where('booking_code', $request->booking_code)->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }
        if (empty($booking->contact_email_to)) {
            return response()->json([
                'success' => false,
                'message' => 'No contact email found for this booking. Cannot send OTP.',
            ], 422);
        }

        $otp = BookingOtp::generate($booking->booking_code);
        Mail::to($booking->contact_email_to)->queue(new BookingCancellationOtp($booking, $otp));

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to ' . $booking->contact_email_to,
        ]);
    }

    public function verifyCancellationOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'booking_code' => 'required|string',
            'otp_code'     => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 'verified' => false, 'cancelled' => false,
                'message' => 'Validation failed.', 'errors' => $validator->errors(),
            ], 422);
        }

        $booking = Booking::where('booking_code', $request->booking_code)->first();

        if (!$booking) {
            return response()->json([
                'success' => false, 'verified' => false, 'cancelled' => false,
                'message' => 'Booking not found.',
            ], 404);
        }

        $latestOtp = BookingOtp::where('booking_code', $request->booking_code)
            ->whereNull('verified_at')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$latestOtp) {
            return response()->json([
                'success' => false, 'verified' => false, 'cancelled' => false,
                'message' => 'No pending OTP found. Please request a new OTP first.',
            ], 422);
        }
        if ($latestOtp->isExpired()) {
            return response()->json([
                'success' => false, 'verified' => false, 'cancelled' => false,
                'message' => 'OTP has expired. Please request a new OTP.',
            ], 422);
        }
        if (!hash_equals($latestOtp->otp_code, $request->otp_code)) {
            return response()->json([
                'success' => false, 'verified' => false, 'cancelled' => false,
                'message' => 'Invalid OTP code.',
            ]);
        }

        $latestOtp->update(['verified_at' => now()]);
        $booking->passengers()->delete();
        $booking->delete();

        return response()->json([
            'success' => true, 'verified' => true, 'cancelled' => true,
            'message' => 'Booking permanently deleted.',
        ]);
    }

    public function verifyPassport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'booking_code'    => 'required|string',
            'passport_number' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $booking = Booking::with('passengers')
            ->where('booking_code', $request->booking_code)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false, 'verified' => false,
                'message' => 'Booking not found.',
            ], 404);
        }

        $normalized = strtoupper(trim($request->passport_number));
        $matched = $booking->passengers->contains(
            fn($p) => strtoupper(trim($p->passport_number)) === $normalized
        );

        if ($matched) {
            return response()->json(['success' => true, 'verified' => true, 'message' => 'Passport verified successfully.']);
        }

        return response()->json([
            'success' => false, 'verified' => false,
            'message' => 'Passport number does not match any passenger on this booking.',
        ], 403);
    }

    public function destroyByEmail(Request $request, string $email): JsonResponse
    {
        $validator = Validator::make(['email' => $email], ['email' => 'required|email']);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $bookings = Booking::where('contact_email_to', $email)->get();

        if ($bookings->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No bookings found for this email.'], 404);
        }

        $count = $bookings->count();
        $bookingIds = $bookings->pluck('id')->toArray();

        Passenger::whereIn('booking_id', $bookingIds)->delete();
        Booking::where('contact_email_to', $email)->delete();

        return response()->json([
            'success' => true,
            'message' => "{$count} booking(s) permanently deleted.",
            'deleted_count' => $count,
        ]);
    }

    public function updatePayment(Request $request, string $bookingCode): JsonResponse
    {
        $booking = Booking::where('booking_code', $bookingCode)->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:0,1,2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $booking->update([
            'payment_method'  => $request->payment_method,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully.',
            'data' => $booking->load('passengers'),
        ]);
    }

    // ─── Private helpers ───────────────────────────────────────────────────────

    private function normalizeSex(string|int $value): int
    {
        if (is_int($value) && in_array($value, [0, 1], true)) {
            return $value;
        }
        $map = ['male' => 0, '0' => 0, '女性' => 1, 'female' => 1, '1' => 1, 'nam' => 0, 'nữ' => 1];
        $lower = mb_strtolower(trim((string) $value));
        return $map[$lower] ?? (int) $value;
    }

    private function normalizeSeatingPreference(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^[A-Ja-j]$/', $value)) {
            return (string) (ord(mb_strtoupper($value)) - ord('A'));
        }
        if (preg_match('/^[0-9]$/', $value)) {
            return $value;
        }
        return $value;
    }

    private function recalculatePricing(Booking $booking): void
    {
        $entryPrice     = (float) ($booking->entry_fast_track_price ?? 0);
        $departurePrice = (float) ($booking->departure_fast_track_price ?? 0);

        $immigrationAddon = $booking->use_immigration_fast_track ? 15.00 : 0.00;
        $tarmacAddon     = $booking->tarmac_pickup ? 60.00 : 0.00;
        $pickupValue     = 0.00;
        $ps = (int) ($booking->pickup_service ?? 0);
        if ($ps === 1)      $pickupValue = 20.00;
        elseif ($ps === 2)  $pickupValue = 25.00;
        elseif ($ps === 3)  $pickupValue = 50.00;

        $subtotal = $entryPrice + $departurePrice
                  + $immigrationAddon
                  + $tarmacAddon
                  + $pickupValue;

        $twoWayDiscount    = (float) ($booking->two_way_discount ?? 0);
        $couponDiscount    = (float) ($booking->coupon_discount_amount ?? 0);
        $nightSurcharge    = (float) ($booking->night_surcharge_value ?? 0);

        $preliminaryCalculation = $subtotal - $twoWayDiscount;
        $tax                     = round($preliminaryCalculation * 0.08, 2);
        $total                   = round($preliminaryCalculation + $tax + $nightSurcharge - $couponDiscount, 2);

        $booking->update([
            'subtotal'                  => $subtotal,
            'preliminary_calculation'   => $preliminaryCalculation,
            'tax'                       => $tax,
            'total'                     => $total,
        ]);
    }

    private function getMissingFields(Booking $booking): array
    {
        $missing = [];

        if (empty($booking->entry_fast_track_option)) {
            $missing[] = 'entry_fast_track_option';
        }

        if ($booking->passengers->isEmpty()) {
            $missing[] = 'passengers';
        } else {
            foreach ($booking->passengers as $idx => $pax) {
                if (empty($pax->last_name))           $missing[] = "passenger_" . ($idx + 1) . "_last_name";
                if (empty($pax->first_name))          $missing[] = "passenger_" . ($idx + 1) . "_first_name";
                if (empty($pax->passport_number))     $missing[] = "passenger_" . ($idx + 1) . "_passport_number";
            }
        }

        return $missing;
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
            'booking_code'               => $booking->booking_code,
            'booking_type'               => $booking->booking_type,
            'use_departure_fast_track'   => $booking->use_departure_fast_track,
            'needs_declaration_support'  => $booking->needs_declaration_support,
            'arrival_airport'            => $booking->arrival_airport,
            'departure_airport_code'      => $booking->departure_airport_code,
            'entry_fast_track_option'    => $booking->entry_fast_track_option,
            'departure_fast_track_option' => $booking->departure_fast_track_option,
            'entry_fast_track_price'     => $booking->entry_fast_track_price,
            'departure_fast_track_price'  => $booking->departure_fast_track_price,
            // Flattened arrival
            'arrival_flight_reservation_code'        => $booking->arrival_flight_reservation_code,
            'arrival_flight_number'                  => $booking->arrival_flight_number,
            'arrival_date'                          => $booking->arrival_date,
            'arrival_time'                          => $booking->arrival_time,
            'arrival_phone_number'                  => $booking->arrival_phone_number,
            'arrival_request'                      => $booking->arrival_request,
            'arrival_class_documents'              => $booking->arrival_class_documents,
            'arrival_checked_baggage_availability' => $booking->arrival_checked_baggage_availability,
            // Immigration addons
            'use_immigration_fast_track' => $booking->use_immigration_fast_track,
            'tarmac_pickup'             => $booking->tarmac_pickup,
            'pickup_service'            => $booking->pickup_service,
            'pickup_time'               => $booking->pickup_time,
            // Flattened departure
            'departure_date'                        => $booking->departure_date,
            'departure_phone_number'                => $booking->departure_phone_number,
            'departure_request'                    => $booking->departure_request,
            'departure_class_documents'            => $booking->departure_class_documents,
            'departure_checked_baggage_availability' => $booking->departure_checked_baggage_availability,
            'departure_seating_preferences'        => $booking->departure_seating_preferences,
            'departure_flight_reservation_code'   => $booking->departure_flight_reservation_code,
            'departure_flight_number'            => $booking->departure_flight_number,
            'options'                    => $booking->options,
            // Pricing
            'subtotal'                   => $booking->subtotal,
            'preliminary_calculation'    => $booking->preliminary_calculation,
            'two_way_discount'          => $booking->two_way_discount,
            'coupon_discount_amount'     => $booking->coupon_discount_amount,
            'night_surcharge_value'      => $booking->night_surcharge_value,
            'tax'                        => $booking->tax,
            'total'                      => $booking->total,
            'payment_method'             => $booking->payment_method,
            'user_phone_number'          => $booking->user_phone_number,
            'contact_email_to'           => $booking->contact_email_to,
            'contact_email_cc'           => $booking->contact_email_cc,
            'passengers'                 => $booking->passengers,
            'collected_fields'           => $collected,
            'missing_fields'             => $this->getMissingFields($booking),
            'next_step'                 => $this->determineNextStep($booking, $collected),
            'created_at'                 => $booking->created_at,
            'updated_at'                 => $booking->updated_at,
        ];
    }

    private function determineNextStep(Booking $booking, array $collected): string
    {
        if (!in_array('entry_fast_track_option', $collected)) return 'entry_fast_track_option';
        if (!in_array('passengers', $collected))               return 'passengers';
        if (!in_array('payment_method', $collected))           return 'payment_method';
        return 'confirmed';
    }
}
