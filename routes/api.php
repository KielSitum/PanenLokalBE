<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\MarketPrice;
use App\Http\Controllers\UserVerificationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ProfileController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', function (Request $request) {
        return response()->json($request->user());
    });
    
    Route::post('/verification/submit', [UserVerificationController::class, 'submit']);
    Route::get('/verification/status', [UserVerificationController::class, 'status']);
    Route::post('/profile/update', [ProfileController::class, 'update']);

    Route::post('/listings', [ListingController::class, 'store']);

    Route::group(['prefix' => 'admin'], function() { 
        // Note: Cek role Admin harus dilakukan di UserVerificationController.php
        Route::get('/verifications/pending', [UserVerificationController::class, 'getPendingSubmissions']);
        Route::post('/verifications/status/{userId}', [UserVerificationController::class, 'updateStatus']);
    });
});

Route::get('/market-prices', function () {
    return \App\Models\MarketPrice::orderBy('commodity')->get();
});

Route::get('/listings/active', [ListingController::class, 'getActiveListings']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/listings', [ListingController::class, 'index']);
    Route::post('/listings', [ListingController::class, 'store']);
    Route::put('/listings/{id}', [ListingController::class, 'update']);
    Route::post('/listings/{id}/mark-sold', [ListingController::class, 'markAsSold']);
    Route::delete('/listings/{id}', [ListingController::class, 'destroy']); // opsional
});

