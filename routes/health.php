<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health Check Routes
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {
    try {
        // Check database connection
        DB::connection()->getPdo();
        $database = 'healthy';
    } catch (\Exception $e) {
        $database = 'unhealthy';
    }

    try {
        // Check cache/redis connection
        Cache::put('health_check', 'ok', 10);
        $cache_result = Cache::get('health_check');
        $cache = ($cache_result === 'ok') ? 'healthy' : 'unhealthy';
    } catch (\Exception $e) {
        $cache = 'unhealthy';
    }

    $status = ($database === 'healthy' && $cache === 'healthy') ? 'healthy' : 'unhealthy';

    return response()->json([
        'status' => $status,
        'timestamp' => now(),
        'services' => [
            'database' => $database,
            'cache' => $cache,
        ],
        'app' => [
            'name' => config('app.name'),
            'env' => config('app.env'),
            'debug' => config('app.debug'),
        ],
    ], $status === 'healthy' ? 200 : 503);
});
