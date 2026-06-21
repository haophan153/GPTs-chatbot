<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    Illuminate\Support\Facades\Mail::raw('Test OTP Email', function($m) {
        $m->to('haophan153204@gmail.com')->subject('Test OTP');
    });
    echo "SUCCESS: Email sent\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
