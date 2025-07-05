<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Service provider for custom API response macros.
 */
class ResponseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /**
         * Success Response Macro
         *
         * @param  mixed  $data
         * @param  string|null  $message
         * @param  int  $code
         * @return \Illuminate\Http\JsonResponse
         */
        Response::macro('apiSuccess', function (mixed $data, ?string $message = null, int $code = SymfonyResponse::HTTP_OK): \Illuminate\Http\JsonResponse {
            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => $data,
            ], $code);
        });

        /**
         * Error Response Macro
         *
         * @param  string|null  $message
         * @param  int  $code
         * @param  mixed|null  $data
         * @param  mixed|null  $error
         * @return \Illuminate\Http\JsonResponse
         */
        Response::macro('apiError', function (?string $message, int $code, mixed $data = null, mixed $error = null): \Illuminate\Http\JsonResponse {
            return response()->json([
                'status' => false,
                'message' => $message,
                'data' => $data,
                'error' => $error,
            ], $code);
        });
    }
}
