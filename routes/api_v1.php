<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['throttle:api', 'api.logger'])->group(function () {
    Route::get('/', function (Request $request) {
        return 'Laravel Blog API V1 Root is working';
    })->name('api.v1.status');

    // Auth Routes
    Route::post('/auth/login', \App\Http\Controllers\Api\V1\Auth\LoginController::class)->name('api.v1.auth.login');
    Route::post('/auth/refresh', \App\Http\Controllers\Api\V1\Auth\RefreshTokenController::class)->name('api.v1.auth.refresh');

    // User Routes
    Route::middleware(['auth:sanctum', 'ability:access-api'])->group(function () {
        Route::get('/me', \App\Http\Controllers\Api\V1\User\MeController::class)->name('api.v1.me');
        Route::put('/profile', \App\Http\Controllers\Api\V1\User\UpdateProfileController::class)->name('api.v1.user.profile.update');

        Route::post('/auth/logout', \App\Http\Controllers\Api\V1\Auth\LogoutController::class)->name('api.v1.auth.logout');
    });

    // Admin Routes
    Route::middleware(['auth:sanctum', 'ability:access-api'])->prefix('admin')->group(function () {
        // User Management
        Route::prefix('users')->group(function () {
            Route::get('/', \App\Http\Controllers\Api\V1\Admin\User\GetUsersController::class)->name('api.v1.admin.users.index');
            Route::post('/', \App\Http\Controllers\Api\V1\Admin\User\CreateUserController::class)->name('api.v1.admin.users.store');
            Route::get('/{id}', \App\Http\Controllers\Api\V1\Admin\User\ShowUserController::class)->name('api.v1.admin.users.show');
            Route::put('/{id}', \App\Http\Controllers\Api\V1\Admin\User\UpdateUserController::class)->name('api.v1.admin.users.update');
            Route::delete('/{id}', \App\Http\Controllers\Api\V1\Admin\User\DeleteUserController::class)->name('api.v1.admin.users.destroy');
            Route::post('/{id}/ban', \App\Http\Controllers\Api\V1\Admin\User\BanUserController::class)->name('api.v1.admin.users.ban');
            Route::post('/{id}/unban', \App\Http\Controllers\Api\V1\Admin\User\UnbanUserController::class)->name('api.v1.admin.users.unban');
            Route::post('/{id}/block', \App\Http\Controllers\Api\V1\Admin\User\BlockUserController::class)->name('api.v1.admin.users.block');
            Route::post('/{id}/unblock', \App\Http\Controllers\Api\V1\Admin\User\UnblockUserController::class)->name('api.v1.admin.users.unblock');
        });

        // Article Management
        Route::prefix('articles')->group(function () {
            Route::get('/', \App\Http\Controllers\Api\V1\Admin\Article\GetArticlesController::class)->name('api.v1.admin.articles.index');
            Route::get('/{id}', \App\Http\Controllers\Api\V1\Admin\Article\ShowArticleController::class)->name('api.v1.admin.articles.show');
            Route::post('/{id}/approve', \App\Http\Controllers\Api\V1\Admin\Article\ApproveArticleController::class)->name('api.v1.admin.articles.approve');
            Route::post('/{id}/feature', \App\Http\Controllers\Api\V1\Admin\Article\FeatureArticleController::class)->name('api.v1.admin.articles.feature');
            Route::post('/{id}/report', \App\Http\Controllers\Api\V1\Admin\Article\ReportArticleController::class)->name('api.v1.admin.articles.report');
        });

        // Comment Management
        Route::prefix('comments')->group(function () {
            Route::get('/', \App\Http\Controllers\Api\V1\Admin\Comment\GetCommentsController::class)->name('api.v1.admin.comments.index');
            Route::post('/{id}/approve', \App\Http\Controllers\Api\V1\Admin\Comment\ApproveCommentController::class)->name('api.v1.admin.comments.approve');
            Route::delete('/{id}', \App\Http\Controllers\Api\V1\Admin\Comment\DeleteCommentController::class)->name('api.v1.admin.comments.destroy');
        });

        // Newsletter Management
        Route::prefix('newsletter')->group(function () {
            Route::get('/subscribers', \App\Http\Controllers\Api\V1\Admin\Newsletter\GetSubscribersController::class)->name('api.v1.admin.newsletter.subscribers.index');
            Route::delete('/subscribers/{id}', \App\Http\Controllers\Api\V1\Admin\Newsletter\DeleteSubscriberController::class)->name('api.v1.admin.newsletter.subscribers.destroy');
        });

        // Notification Management
        Route::prefix('notifications')->group(function () {
            Route::get('/', \App\Http\Controllers\Api\V1\Admin\Notification\GetNotificationsController::class)->name('api.v1.admin.notifications.index');
            Route::post('/', \App\Http\Controllers\Api\V1\Admin\Notification\CreateNotificationController::class)->name('api.v1.admin.notifications.store');
        });
    });

    // Public Routes
    Route::middleware(['optional.sanctum'])->group(function () {
        // Article Routes
        Route::prefix('articles')->group(function () {
            Route::get('/', \App\Http\Controllers\Api\V1\Article\GetArticlesController::class)->name('api.v1.articles.index');
            Route::get('/{slug}', \App\Http\Controllers\Api\V1\Article\ShowArticleController::class)->name('api.v1.articles.show');
            Route::get('/{article:slug}/comments', \App\Http\Controllers\Api\V1\Article\GetCommentsController::class)->name('api.v1.articles.comments.index');
        });

        // Category Routes
        Route::get('categories', \App\Http\Controllers\Api\V1\Category\GetCategoriesController::class)->name('api.v1.categories.index');

        // Tag Routes
        Route::get('tags', \App\Http\Controllers\Api\V1\Tag\GetTagsController::class)->name('api.v1.tags.index');

        // Newsletter Routes
        Route::prefix('newsletter')->group(function () {
            Route::post('/subscribe', \App\Http\Controllers\Api\V1\Newsletter\SubscribeController::class)->name('api.v1.newsletter.subscribe');
            Route::post('/unsubscribe', \App\Http\Controllers\Api\V1\Newsletter\UnsubscribeController::class)->name('api.v1.newsletter.unsubscribe');
            Route::post('/verify', \App\Http\Controllers\Api\V1\Newsletter\VerifySubscriptionController::class)->name('api.v1.newsletter.verify');
            Route::post('/verify-unsubscribe', \App\Http\Controllers\Api\V1\Newsletter\VerifyUnsubscriptionController::class)->name('api.v1.newsletter.verify-unsubscribe');
        });
    });

});
