<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\KomunitasController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::post('/users', [AuthController::class, 'register']);
Route::post('/users/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::get('/users/current', [AuthController::class, 'currentUser']);
    Route::patch('/users/current', [AuthController::class, 'updateCurrentUser']);
    Route::delete('/users/logout', [AuthController::class, 'logout']);

    // Court routes
    Route::get('/courts', [CourtController::class, 'index']);
    Route::get('/courts/{id}', [CourtController::class, 'show']);

    // Admin only court routes
    Route::middleware('admin')->group(function () {
        Route::post('/courts', [CourtController::class, 'store']);
        Route::put('/courts/{id}', [CourtController::class, 'update']);
        Route::delete('/courts/{id}', [CourtController::class, 'destroy']);
    });

    // Booking routes
    Route::get('/bookings/check-availability', [BookingController::class, 'checkAvailability']);
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::delete('/bookings/{booking_id}', [BookingController::class, 'destroy']);

    // Komunitas routes
    Route::get('/komunitas', [KomunitasController::class, 'index']);
    Route::post('/komunitas', [KomunitasController::class, 'store']);
    Route::get('/komunitas/{id}', [KomunitasController::class, 'show']);
    Route::put('/komunitas/{id}', [KomunitasController::class, 'update'])->middleware('komunitas.creator');
    Route::delete('/komunitas/{id}/delete', [KomunitasController::class, 'destroy'])->middleware('komunitas.creator');

    // Komunitas member routes
    Route::get('/komunitas/{id}/members/requests', [KomunitasController::class, 'showRequests'])->middleware('komunitas.creator');
    Route::post('/komunitas/{id}/members/requests', [KomunitasController::class, 'handleRequest'])->middleware('komunitas.creator');
    Route::post('/komunitas/{id}/join', [KomunitasController::class, 'joinKomunitas']);
    Route::post('/komunitas/{id}/leave', [KomunitasController::class, 'leaveKomunitas']);
});
