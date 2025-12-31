<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Helper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
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
     * @param  Throwable  $e  The exception to handle
     * @param  Request|null  $request  The request instance (optional, for context)
     * @param  string|null  $customMessage  Custom error message (optional)
     */
    protected function handleException(Throwable $e, ?Request $request = null, ?string $customMessage = null): JsonResponse
    {
        $logLevel = $this->determineLogLevel($e);
        $statusCode = $this->determineStatusCode($e);

        // Build additional context (exception already has file, line, trace, message)
        $context = $this->buildLogContext($e, $request);

        // Include exception in context for Laravel's logger to extract its details
        $context['exception'] = $e;

        // Log the exception with additional context
        // Laravel automatically extracts exception details (file, line, trace, message)
        Log::{$logLevel}(
            $this->buildLogMessage($e),
            $context
        );

        // Determine error message
        $message = $customMessage ?? $this->determineErrorMessage($e, $statusCode);

        /**
         * Error response based on exception type
         *
         * @status 400-500
         *
         * @body array{status: false, message: string, data: null, error: null}
         */
        return response()->apiError($message, $statusCode);
    }

    /**
     * Build comprehensive log context for exception logging.
     *
     * Adds additional useful context to the exception.
     * Note: Exception already contains file, line, trace, message, etc.
     *
     * @param  Throwable  $e  The exception
     * @param  Request|null  $request  The request instance
     * @return array<string, mixed>
     */
    protected function buildLogContext(Throwable $e, ?Request $request = null): array
    {
        $context = [
            'controller' => static::class,
        ];

        if ($request !== null) {
            /** @var \App\Models\User|null $user */
            $user = $request->user();

            $context['request'] = [
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
            $context['request']['input'] = $this->sanitizeSensitiveData($input);

            // Add user information if authenticated
            if ($user !== null) {
                $context['user'] = [
                    'id' => $user->id,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('slug')->toArray(),
                ];
            }
        }

        // Add environment context
        $context['environment'] = [
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
        ];

        // Add previous exception summary if chained
        if ($e->getPrevious() !== null) {
            $context['previous_exception'] = [
                'class' => get_class($e->getPrevious()),
                'message' => $e->getPrevious()->getMessage(),
            ];
        }

        return $context;
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
     * Determine the appropriate HTTP status code based on exception type.
     *
     * @param  Throwable  $e  The exception
     * @return int HTTP status code
     */
    protected function determineStatusCode(Throwable $e): int
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
     * Build a descriptive log message.
     *
     * @param  Throwable  $e  The exception
     * @return string Log message
     */
    protected function buildLogMessage(Throwable $e): string
    {
        $controllerName = class_basename(static::class);

        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel() ?? 'Model');

            return "{$controllerName}: {$model} not found";
        }

        if ($e instanceof UnauthorizedException) {
            return "{$controllerName}: Authentication failed";
        }

        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return "{$controllerName}: Authorization failed";
        }

        return "{$controllerName}: Exception occurred";
    }

    /**
     * Determine the appropriate error message for the response.
     *
     * @param  Throwable  $e  The exception
     * @param  int  $statusCode  HTTP status code
     * @return string Error message
     */
    protected function determineErrorMessage(Throwable $e, int $statusCode): string
    {
        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel() ?? 'Model');

            return match ($model) {
                'Article' => __('common.article_not_found'),
                'User' => __('common.user_not_found'),
                'Comment' => __('common.comment_not_found'),
                'Category' => __('common.category_not_found'),
                'Tag' => __('common.tag_not_found'),
                'NewsletterSubscriber' => __('common.subscriber_not_found'),
                'Notification' => __('common.notification_not_found'),
                'Role' => __('common.role_not_found'),
                'Permission' => __('common.permission_not_found'),
                default => __('common.not_found'),
            };
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
}
