<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/', function (Request $request) {
        return 'Laravel Blog API V1 Root is working';
    });

    // User Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', function (Request $request) {
            return auth()->user();
        });
    });
});
