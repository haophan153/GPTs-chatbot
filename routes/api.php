<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\ApiLogController;
use Illuminate\Support\Facades\Route;

Route::prefix('bookings')->middleware(['api.key', 'log.api'])->group(function () {
    Route::get('/', [BookingController::class, 'index']);
    Route::post('/', [BookingController::class, 'store']);
    Route::get('/lookup', [BookingController::class, 'lookup']);
    Route::delete('/by-email/{email}', [BookingController::class, 'destroyByEmail']);
    Route::post('/init', [BookingController::class, 'init']);
    Route::post('/verify-passport', [BookingController::class, 'verifyPassport']);
    Route::post('/cancel/request-otp', [BookingController::class, 'requestCancellationOtp']);
    Route::post('/cancel/verify-otp', [BookingController::class, 'verifyCancellationOtp']);
    Route::post('/cancel', [BookingController::class, 'cancelWithVerification']);
    Route::patch('/{bookingCode}', [BookingController::class, 'updateStep']);
    Route::get('/{bookingCode}', [BookingController::class, 'show']);
    Route::put('/{bookingCode}', [BookingController::class, 'update']);
    Route::delete('/{bookingCode}', [BookingController::class, 'destroy']);
    Route::post('/{bookingCode}/confirm', [BookingController::class, 'confirm']);
    Route::post('/{bookingCode}/payment', [BookingController::class, 'updatePayment']);
    Route::get('/{bookingCode}/logs', [ApiLogController::class, 'show']);
});

Route::get('/logs', [ApiLogController::class, 'index'])->middleware('api.key');
