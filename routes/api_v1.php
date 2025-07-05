<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/', function (Request $request) {
        return 'Laravel Blog API V1 Root is working';
    })->name('api.v1.status');

    // Auth
    Route::post('/auth/login', \App\Http\Controllers\Api\V1\Auth\LoginController::class)->name('api.v1.auth.login');

    // User Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', function (Request $request) {
            return auth()->user();
        });
    });
});
