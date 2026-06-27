<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$b = App\Models\Booking::where('booking_code', 'VJP-F6E0E72F')->first();

if (! $b) {
    echo "NOT FOUND\n";
    exit(0);
}

echo "FOUND\n";
echo "  status                            = " . ($b->status ?? 'NULL') . "\n";
echo "  arrival_flight_reservation_code   = " . ($b->arrival_flight_reservation_code ?? 'NULL') . "\n";
echo "  arrival_flight_number             = " . ($b->arrival_flight_number ?? 'NULL') . "\n";
echo "  arrival_date                      = " . ($b->arrival_date ?? 'NULL') . "\n";
echo "  arrival_time                      = " . ($b->arrival_time ?? 'NULL') . "\n";
echo "  arrival_class_documents           = " . ($b->arrival_class_documents ?? 'NULL') . "\n";
echo "  arrival_checked_baggage_availability = " . ($b->arrival_checked_baggage_availability ?? 'NULL') . "\n";
echo "  departure_flight_reservation_code = " . ($b->departure_flight_reservation_code ?? 'NULL') . "\n";
echo "  departure_flight_number           = " . ($b->departure_flight_number ?? 'NULL') . "\n";
echo "  departure_date                    = " . ($b->departure_date ?? 'NULL') . "\n";
echo "  pickup_time                       = " . ($b->pickup_time ?? 'NULL') . "\n";
echo "  departure_class_documents         = " . ($b->departure_class_documents ?? 'NULL') . "\n";
echo "  departure_checked_baggage_availability = " . ($b->departure_checked_baggage_availability ?? 'NULL') . "\n";
echo "  created_at                        = {$b->created_at}\n";
echo "  updated_at                        = {$b->updated_at}\n";