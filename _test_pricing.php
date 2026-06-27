<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apiKey = 'ba47eddde203507e6d944df83011fd5d44aea672b08b9eba4adf5af363e3262e';
$client = new \GuzzleHttp\Client(['timeout' => 30, 'verify' => false]);

echo "=== TEST two_way_discount logic ===\n\n";

// Test 1: both → should have discount
echo "[1] booking_type=both → expect two_way_discount=\$5.00\n";
$res = $client->request('POST', 'http://127.0.0.1:8001/api/bookings/init', [
    'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'X-API-Key' => $apiKey],
    'json' => [
        'booking_type' => 'both',
        'arrival_airport' => '0',
        'departure_airport' => '0',
        'entry_fast_track_option' => '4',
        'departure_fast_track_option' => '2',
        'user_phone_number' => '+84898133490',
        'contact_email_to' => 'test@example.com',
    ],
]);
$d = json_decode($res->getBody()->getContents(), true)['data'] ?? [];
echo "  two_way_discount    : " . ($d['two_way_discount'] ?? 'N/A') . "\n";
echo "  night_surcharge     : " . ($d['night_surcharge_value'] ?? 'N/A') . "\n";
echo "  subtotal            : " . ($d['subtotal'] ?? 'N/A') . "\n";
echo "  preliminary_calc    : " . ($d['preliminary_calculation'] ?? 'N/A') . "\n";
echo "  tax                : " . ($d['tax'] ?? 'N/A') . "\n";
echo "  total              : " . ($d['total'] ?? 'N/A') . "\n";
echo "  next_step          : " . ($d['next_step'] ?? 'N/A') . "\n";
echo "  PASS: " . ((($d['two_way_discount'] ?? 0) == 5.00) ? "YES" : "NO") . "\n\n";

// Test 2: arrival → NO discount
echo "[2] booking_type=arrival → expect two_way_discount=0\n";
$res = $client->request('POST', 'http://127.0.0.1:8001/api/bookings/init', [
    'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'X-API-Key' => $apiKey],
    'json' => [
        'booking_type' => 'arrival',
        'arrival_airport' => '0',
        'entry_fast_track_option' => '4',
        'user_phone_number' => '+84898133490',
        'contact_email_to' => 'test2@example.com',
    ],
]);
$d = json_decode($res->getBody()->getContents(), true)['data'] ?? [];
echo "  two_way_discount    : " . ($d['two_way_discount'] ?? 'N/A') . "\n";
echo "  subtotal            : " . ($d['subtotal'] ?? 'N/A') . "\n";
echo "  preliminary_calc    : " . ($d['preliminary_calculation'] ?? 'N/A') . "\n";
echo "  tax                : " . ($d['tax'] ?? 'N/A') . "\n";
echo "  total              : " . ($d['total'] ?? 'N/A') . "\n";
echo "  next_step          : " . ($d['next_step'] ?? 'N/A') . "\n";
echo "  PASS: " . ((($d['two_way_discount'] ?? -1) == 0) ? "YES" : "NO") . "\n\n";

// Test 3: departure → NO discount
echo "[3] booking_type=departure → expect two_way_discount=0\n";
$res = $client->request('POST', 'http://127.0.0.1:8001/api/bookings/init', [
    'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'X-API-Key' => $apiKey],
    'json' => [
        'booking_type' => 'departure',
        'arrival_airport' => '0',
        'departure_airport' => '0',
        'entry_fast_track_option' => '4',
        'departure_fast_track_option' => '2',
        'user_phone_number' => '+84898133490',
        'contact_email_to' => 'test3@example.com',
    ],
]);
$d = json_decode($res->getBody()->getContents(), true)['data'] ?? [];
echo "  two_way_discount    : " . ($d['two_way_discount'] ?? 'N/A') . "\n";
echo "  subtotal            : " . ($d['subtotal'] ?? 'N/A') . "\n";
echo "  preliminary_calc    : " . ($d['preliminary_calculation'] ?? 'N/A') . "\n";
echo "  tax                : " . ($d['tax'] ?? 'N/A') . "\n";
echo "  total              : " . ($d['total'] ?? 'N/A') . "\n";
echo "  next_step          : " . ($d['next_step'] ?? 'N/A') . "\n";
echo "  PASS: " . ((($d['two_way_discount'] ?? -1) == 0) ? "YES" : "NO") . "\n\n";

echo "Done.\n";
