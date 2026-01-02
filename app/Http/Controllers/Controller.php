<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ExceptionHandlerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Base controller for all application controllers.
 *
 * @internal
 */
abstract class Controller
{
    /**
     * Handle exceptions with comprehensive logging and standardized error responses.
     *
     * Delegates to the centralized ExceptionHandlerService for consistent handling.
     *
     * @param  Throwable  $e  The exception to handle
     * @param  Request|null  $request  The request instance (optional, for context)
     * @param  string|null  $customMessage  Custom error message (optional)
     * @return JsonResponse Standardized API error response
     */
    protected function handleException(Throwable $e, ?Request $request = null, ?string $customMessage = null): JsonResponse
    {
        $exceptionHandler = app(ExceptionHandlerService::class);

        return $exceptionHandler->handle(
            $e,
            $request,
            $customMessage,
            class_basename(static::class)
        );
    }
}
