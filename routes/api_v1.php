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
    Route::post('/auth/forgot-password', \App\Http\Controllers\Api\V1\Auth\ForgotPasswordController::class)->name('api.v1.auth.password.forgot');
    Route::post('/auth/reset-password', \App\Http\Controllers\Api\V1\Auth\ResetPasswordController::class)->name('api.v1.auth.password.reset');

    // User Routes
    Route::middleware(['auth:sanctum', 'ability:access-api'])->group(function () {
        Route::get('/me', \App\Http\Controllers\Api\V1\User\MeController::class)->name('api.v1.me');
        Route::put('/profile', \App\Http\Controllers\Api\V1\User\UpdateProfileController::class)->name('api.v1.user.profile.update');

        Route::post('/auth/logout', \App\Http\Controllers\Api\V1\Auth\LogoutController::class)->name('api.v1.auth.logout');

        // Article Management (accessible by authenticated users - users can manage their own articles, admins can manage all)
        Route::prefix('articles')->group(function () {
            Route::post('/', \App\Http\Controllers\Api\V1\Admin\Article\CreateArticleController::class)->name('api.v1.articles.store');
            Route::post('/{article}/archive', \App\Http\Controllers\Api\V1\Admin\Article\ArchiveArticleController::class)->name('api.v1.articles.archive');
            Route::post('/{article}/restore', \App\Http\Controllers\Api\V1\Admin\Article\RestoreArticleController::class)->name('api.v1.articles.restore');
            Route::post('/{article}/trash', \App\Http\Controllers\Api\V1\Admin\Article\TrashArticleController::class)->name('api.v1.articles.trash');
            Route::post('/{article}/restore-from-trash', \App\Http\Controllers\Api\V1\Admin\Article\RestoreFromTrashController::class)->name('api.v1.articles.restore-from-trash');
        });
    });

    // Admin Routes
    Route::middleware(['auth:sanctum', 'ability:access-api'])->prefix('admin')->group(function () {
        // User Management
        Route::prefix('users')->group(function () {
            Route::get('/', \App\Http\Controllers\Api\V1\Admin\User\GetUsersController::class)->name('api.v1.admin.users.index');
            Route::post('/', \App\Http\Controllers\Api\V1\Admin\User\CreateUserController::class)->name('api.v1.admin.users.store');
            Route::get('/{user}', \App\Http\Controllers\Api\V1\Admin\User\ShowUserController::class)->name('api.v1.admin.users.show');
            Route::put('/{user}', \App\Http\Controllers\Api\V1\Admin\User\UpdateUserController::class)->name('api.v1.admin.users.update');
            Route::delete('/{user}', \App\Http\Controllers\Api\V1\Admin\User\DeleteUserController::class)->name('api.v1.admin.users.destroy');
            Route::post('/{user}/ban', \App\Http\Controllers\Api\V1\Admin\User\BanUserController::class)->name('api.v1.admin.users.ban');
            Route::post('/{user}/unban', \App\Http\Controllers\Api\V1\Admin\User\UnbanUserController::class)->name('api.v1.admin.users.unban');
            Route::post('/{user}/block', \App\Http\Controllers\Api\V1\Admin\User\BlockUserController::class)->name('api.v1.admin.users.block');
            Route::post('/{user}/unblock', \App\Http\Controllers\Api\V1\Admin\User\UnblockUserController::class)->name('api.v1.admin.users.unblock');
        });

        // Article Management (Admin-only actions)
        Route::prefix('articles')->group(function () {
            Route::get('/', \App\Http\Controllers\Api\V1\Admin\Article\GetArticlesController::class)->name('api.v1.admin.articles.index');
            Route::get('/{article}', \App\Http\Controllers\Api\V1\Admin\Article\ShowArticleController::class)->name('api.v1.admin.articles.show');
            Route::post('/{article}/report', \App\Http\Controllers\Api\V1\Admin\Article\ReportArticleController::class)->name('api.v1.admin.articles.report');
            Route::post('/{article}/approve', \App\Http\Controllers\Api\V1\Admin\Article\ApproveArticleController::class)->name('api.v1.admin.articles.approve');
            Route::post('/{article}/pin', \App\Http\Controllers\Api\V1\Admin\Article\PinArticleController::class)->name('api.v1.admin.articles.pin');
            Route::post('/{article}/unpin', \App\Http\Controllers\Api\V1\Admin\Article\UnpinArticleController::class)->name('api.v1.admin.articles.unpin');
            Route::post('/{article}/feature', \App\Http\Controllers\Api\V1\Admin\Article\FeatureArticleController::class)->name('api.v1.admin.articles.feature');
        });

        // Comment Management
        Route::prefix('comments')->group(function () {
            Route::get('/', \App\Http\Controllers\Api\V1\Admin\Comment\GetCommentsController::class)->name('api.v1.admin.comments.index');
            Route::post('/{comment}/approve', \App\Http\Controllers\Api\V1\Admin\Comment\ApproveCommentController::class)->name('api.v1.admin.comments.approve');
            Route::delete('/{comment}', \App\Http\Controllers\Api\V1\Admin\Comment\DeleteCommentController::class)->name('api.v1.admin.comments.destroy');
        });

        // Newsletter Management
        Route::prefix('newsletter')->group(function () {
            Route::get('/subscribers', \App\Http\Controllers\Api\V1\Admin\Newsletter\GetSubscribersController::class)->name('api.v1.admin.newsletter.subscribers.index');
            Route::delete('/subscribers/{newsletterSubscriber}', \App\Http\Controllers\Api\V1\Admin\Newsletter\DeleteSubscriberController::class)->name('api.v1.admin.newsletter.subscribers.destroy');
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
