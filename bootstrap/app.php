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
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->apiError(
                    message: $e->getMessage(),
                    code: \Symfony\Component\HttpFoundation\Response::HTTP_UNPROCESSABLE_ENTITY,
                    data: null,
                    error: $e->errors()
                );
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $model = class_basename($e->getModel());
                $message = match ($model) {
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

                return response()->apiError(
                    message: $message,
                    code: \Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND
                );
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                // Check if the previous exception was a ModelNotFoundException
                $previous = $e->getPrevious();
                if ($previous instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    $model = class_basename($previous->getModel());
                    $message = match ($model) {
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

                    return response()->apiError(
                        message: $message,
                        code: \Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND
                    );
                }

                // Generic 404 for other NotFoundHttpException cases
                return response()->apiError(
                    message: __('common.not_found'),
                    code: \Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND
                );
            }
        });
    })->create();
