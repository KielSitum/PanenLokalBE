<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\MarketPrice;
use App\Http\Controllers\UserVerificationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\TransactionController; 

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

Route::get('/image/{filename}', function ($filename) {
    $path = storage_path('app/public/listings/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', function (Request $request) {
        return response()->json($request->user());
    });
    Route::post('/verification/submit', [UserVerificationController::class, 'submit']);
    Route::get('/verification/status', [UserVerificationController::class, 'status']);
    Route::post('/profile/update', [ProfileController::class, 'update']);

    Route::post('/listings', [ListingController::class, 'store']);

    
    // ✅ Transaction Routes (DIPERBAIKI)
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/farmer/transactions', [TransactionController::class, 'farmerTransactions']);
    Route::put('/transactions/{id}/status', [TransactionController::class, 'updateStatus']);
    Route::put('/transactions/listing/{listingId}', [TransactionController::class, 'updateTransactionsByListing']); // ⚠️ UBAH INI
    Route::post('/reviews', [TransactionController::class, 'storeReview']);



    Route::group(['prefix' => 'admin'], function() { 
        Route::get('/verifications/pending', [UserVerificationController::class, 'getPendingSubmissions']);
        Route::post('/verifications/status/{userId}', [UserVerificationController::class, 'updateStatus']);

        Route::get('/users', [UserManagementController::class, 'getAllUsers']);
        Route::delete('/users/{userId}', [UserManagementController::class, 'deleteUser']);
        Route::put('/users/{userId}/role', [UserManagementController::class, 'updateUserRole']);
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
    Route::delete('/listings/{id}', [ListingController::class, 'destroy']);
});