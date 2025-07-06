<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/', function (Request $request) {
        return 'Laravel Blog API V1 Root is working';
    })->name('api.v1.status');

    // Auth
    Route::post('/auth/login', \App\Http\Controllers\Api\V1\Auth\LoginController::class)->name('api.v1.auth.login');
    Route::post('/auth/refresh', \App\Http\Controllers\Api\V1\Auth\RefreshTokenController::class)->name('api.v1.auth.refresh');

    Route::middleware(['auth:sanctum', 'ability:access-api'])->group(function () {
        Route::post('/auth/logout', \App\Http\Controllers\Api\V1\Auth\LogoutController::class)->name('api.v1.auth.logout');
    });

    // User Routes
    Route::middleware(['auth:sanctum', 'ability:access-api'])->group(function () {
        Route::get('/me', function (Request $request) {
            return auth()->user();
        });
    });
});
