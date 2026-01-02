<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\ExceptionHelper;
use App\Support\Helper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Centralized exception handler service for consistent exception handling across the application.
 *
 * This service provides a single source of truth for:
 * - Exception logging (with validation exception filtering)
 * - HTTP status code determination
 * - Error message determination
 * - API error response formatting
 */
final class ExceptionHandlerService
{
    /**
     * Handle exception with logging and return standardized API error response.
     *
     * @param  Throwable  $e  The exception to handle
     * @param  Request|null  $request  The request instance (optional, for context)
     * @param  string|null  $customMessage  Custom error message (optional)
     * @param  string|null  $context  Additional context for logging (e.g., controller class name)
     * @return JsonResponse Standardized API error response
     */
    public function handle(Throwable $e, ?Request $request = null, ?string $customMessage = null, ?string $context = null): JsonResponse
    {
        $statusCode = $this->determineStatusCode($e);

        // Log exception if it should be logged (validation exceptions are excluded)
        if ($this->shouldLogException($e)) {
            $this->logException($e, $request, $context);
        }

        // Determine error message
        $message = $customMessage ?? $this->determineErrorMessage($e, $statusCode);

        // Handle ValidationException with errors
        $errors = null;
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            $errors = $e->errors();
        }

        return response()->apiError($message, $statusCode, null, $errors);
    }

    /**
     * Determine if exception should be logged.
     *
     * @param  Throwable  $e  The exception
     * @return bool True if exception should be logged
     */
    public function shouldLogException(Throwable $e): bool
    {
        // Don't log during tests to improve test performance
        if (app()->environment('testing') || app()->runningUnitTests()) {
            return false;
        }

        // Don't log validation exceptions (they're expected client errors)
        return ! ($e instanceof \Illuminate\Validation\ValidationException);
    }

    /**
     * Log exception with comprehensive context.
     *
     * @param  Throwable  $e  The exception
     * @param  Request|null  $request  The request instance
     * @param  string|null  $context  Additional context (e.g., controller class name)
     */
    public function logException(Throwable $e, ?Request $request = null, ?string $context = null): void
    {
        $logLevel = $this->determineLogLevel($e);
        $logContext = $this->buildLogContext($e, $request, $context);

        // Include exception in context for Laravel's logger to extract its details
        $logContext['exception'] = $e;

        Log::{$logLevel}(
            $this->buildLogMessage($e, $context),
            $logContext
        );
    }

    /**
     * Determine the appropriate HTTP status code based on exception type.
     *
     * @param  Throwable  $e  The exception
     * @return int HTTP status code
     */
    public function determineStatusCode(Throwable $e): int
    {
        if ($e instanceof UnauthorizedException) {
            return Response::HTTP_UNAUTHORIZED;
        }

        if ($e instanceof ModelNotFoundException) {
            return Response::HTTP_NOT_FOUND;
        }

        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return Response::HTTP_FORBIDDEN;
        }

        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return Response::HTTP_UNPROCESSABLE_ENTITY;
        }

        if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return Response::HTTP_NOT_FOUND;
        }

        // Default to 500 for unhandled exceptions
        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    /**
     * Determine the appropriate error message for the response.
     *
     * @param  Throwable  $e  The exception
     * @param  int  $statusCode  HTTP status code
     * @return string Error message
     */
    public function determineErrorMessage(Throwable $e, int $statusCode): string
    {
        if ($e instanceof ModelNotFoundException) {
            return ExceptionHelper::getModelNotFoundMessage($e);
        }

        // Check if NotFoundHttpException has a ModelNotFoundException as previous exception
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            $previous = $e->getPrevious();
            if ($previous instanceof ModelNotFoundException) {
                return ExceptionHelper::getModelNotFoundMessage($previous);
            }

            return __('common.not_found');
        }

        if ($e instanceof \Illuminate\Validation\ValidationException) {
            // For ValidationException, use Laravel's default message format which includes "(and X more errors)"
            // This matches the expected format in tests and provides better UX
            return $e->getMessage();
        }

        if ($e instanceof UnauthorizedException) {
            return $e->getMessage() ?: __('auth.failed');
        }

        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return $e->getMessage() ?: __('common.forbidden');
        }

        if ($statusCode === Response::HTTP_NOT_FOUND) {
            return __('common.not_found');
        }

        // Default error message for server errors
        return __('common.something_went_wrong');
    }

    /**
     * Determine the appropriate log level based on exception type.
     *
     * @param  Throwable  $e  The exception
     * @return string Log level (error, warning, etc.)
     */
    protected function determineLogLevel(Throwable $e): string
    {
        // Client errors (4xx) are warnings, server errors (5xx) are errors
        if ($e instanceof ModelNotFoundException || $e instanceof UnauthorizedException) {
            return 'warning';
        }

        return 'error';
    }

    /**
     * Build comprehensive log context for exception logging.
     *
     * @param  Throwable  $e  The exception
     * @param  Request|null  $request  The request instance
     * @param  string|null  $context  Additional context (e.g., controller class name)
     * @return array<string, mixed>
     */
    protected function buildLogContext(Throwable $e, ?Request $request = null, ?string $context = null): array
    {
        $logContext = [];

        if ($context !== null) {
            $logContext['context'] = $context;
        }

        if ($request !== null) {
            /** @var \App\Models\User|null $user */
            $user = $request->user();

            $logContext['request'] = [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => Helper::getRealIpAddress($request),
                'user_agent' => $request->userAgent(),
                'route' => $request->route()?->getName(),
                'route_params' => $request->route()?->parameters(),
            ];

            // Include request input (but sanitize sensitive data)
            /** @var array<string, mixed> $input */
            $input = $request->all();
            $logContext['request']['input'] = $this->sanitizeSensitiveData($input);

            // Add user information if authenticated
            if ($user !== null) {
                $logContext['user'] = [
                    'id' => $user->id,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('slug')->toArray(),
                ];
            }
        }

        // Add environment context
        $logContext['environment'] = [
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
        ];

        // Add previous exception summary if chained
        if ($e->getPrevious() !== null) {
            $logContext['previous_exception'] = [
                'class' => get_class($e->getPrevious()),
                'message' => $e->getPrevious()->getMessage(),
            ];
        }

        return $logContext;
    }

    /**
     * Build a descriptive log message.
     *
     * @param  Throwable  $e  The exception
     * @param  string|null  $context  Additional context (e.g., controller class name)
     * @return string Log message
     */
    protected function buildLogMessage(Throwable $e, ?string $context = null): string
    {
        $prefix = $context !== null ? "{$context}: " : '';

        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel() ?? 'Model');

            return "{$prefix}{$model} not found";
        }

        if ($e instanceof UnauthorizedException) {
            return "{$prefix}Authentication failed";
        }

        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return "{$prefix}Authorization failed";
        }

        return "{$prefix}Exception occurred";
    }

    /**
     * Sanitize sensitive data from request input for logging.
     *
     * @param  array<string, mixed>  $input  Request input data
     * @return array<string, mixed> Sanitized input
     */
    protected function sanitizeSensitiveData(array $input): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'current_password', 'token', 'secret', 'api_key'];

        foreach ($sensitiveKeys as $key) {
            if (isset($input[$key])) {
                $input[$key] = '***REDACTED***';
            }
        }

        return $input;
    }
}
