<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: [
            __DIR__.'/../routes/api.php',
            __DIR__.'/../routes/api_v1.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ability' => \App\Http\Middleware\CheckTokenAbility::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'api.logger' => \App\Http\Middleware\ApiLogger::class,
            'optional.sanctum' => \App\Http\Middleware\OptionalSanctumAuthenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->apiError(
                    message: $e->getMessage(),
                    code: \Symfony\Component\HttpFoundation\Response::HTTP_UNPROCESSABLE_ENTITY,
                    data: null,
                    error: $e->errors()
                );
            }
        });
    })->create();
