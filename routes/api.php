<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PixController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::get('/pix', [PixController::class, 'index']);
    Route::post('/pix', [PixController::class, 'store']);
    Route::get('/pix/stats', [PixController::class, 'stats']);
    Route::post('/pix/{token}', [PixController::class, 'confirmPayment']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/beams-interest', [NotificationController::class, 'beamsInterest']);
});
