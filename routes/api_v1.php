<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/', function (Request $request) {
        return 'Laravel Blog API V1 Root is working';
    })->name('api.v1.status');

    // Auth Routes
    Route::post('/auth/login', \App\Http\Controllers\Api\V1\Auth\LoginController::class)->name('api.v1.auth.login');
    Route::post('/auth/refresh', \App\Http\Controllers\Api\V1\Auth\RefreshTokenController::class)->name('api.v1.auth.refresh');

    // User Routes
    Route::middleware(['auth:sanctum', 'ability:access-api'])->group(function () {
        Route::get('/me', \App\Http\Controllers\Api\V1\User\MeController::class);

        Route::post('/auth/logout', \App\Http\Controllers\Api\V1\Auth\LogoutController::class)->name('api.v1.auth.logout');
    });

    // Article Routes (Public)
    Route::prefix('articles')->group(function () {
        Route::get('/', \App\Http\Controllers\Api\V1\Article\GetArticlesController::class)->name('api.v1.articles.index');
        Route::get('/{slug}', \App\Http\Controllers\Api\V1\Article\ShowArticleController::class)->name('api.v1.articles.show');
    });
});
