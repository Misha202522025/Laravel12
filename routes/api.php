<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api')->group(function () {
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy']);
    Route::post('/bookings/{booking}/slots', [BookingController::class, 'addSlot']);
    Route::patch('/bookings/{booking}/slots/{slot}', [BookingController::class, 'updateSlot']);
});
