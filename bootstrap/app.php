<?php

use App\Services\ExceptionHandlerService;
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
        // Add security headers to all responses
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->alias([
            'ability' => \App\Http\Middleware\CheckTokenAbility::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'api.logger' => \App\Http\Middleware\ApiLogger::class,
            'optional.sanctum' => \App\Http\Middleware\OptionalSanctumAuthenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Prevent validation exceptions from being logged
        $exceptions->report(function (\Illuminate\Validation\ValidationException $e): bool {
            return false; // Don't report/log validation exceptions
        });

        // Reusable handler for API exception rendering
        $apiExceptionHandler = function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return app(ExceptionHandlerService::class)->handle($e, $request);
            }
        };

        // Register exception handlers for API routes using centralized service
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) use ($apiExceptionHandler) {
            return $apiExceptionHandler($e, $request);
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, \Illuminate\Http\Request $request) use ($apiExceptionHandler) {
            return $apiExceptionHandler($e, $request);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) use ($apiExceptionHandler) {
            return $apiExceptionHandler($e, $request);
        });
    })->create();
