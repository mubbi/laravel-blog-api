<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    return 'Laravel Blog API Root is working';
})->name('api.status');

// Include health check routes
require __DIR__.'/health.php';
