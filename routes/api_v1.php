<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['throttle:api', 'api.logger'])->group(function () {
    Route::get('/', function (Request $request) {
        return 'Laravel Blog API V1 Root is working';
    })->name('api.v1.status');

    // Auth Routes (stricter rate limiting)
    Route::middleware(['throttle:auth'])->group(function () {
        Route::post('/auth/register', \App\Http\Controllers\Api\V1\Auth\RegisterController::class)->name('api.v1.auth.register');
        Route::post('/auth/login', \App\Http\Controllers\Api\V1\Auth\LoginController::class)->name('api.v1.auth.login');
        Route::post('/auth/refresh', \App\Http\Controllers\Api\V1\Auth\RefreshTokenController::class)->name('api.v1.auth.refresh');
        Route::post('/auth/forgot-password', \App\Http\Controllers\Api\V1\Auth\ForgotPasswordController::class)->name('api.v1.auth.password.forgot');
        Route::post('/auth/reset-password', \App\Http\Controllers\Api\V1\Auth\ResetPasswordController::class)->name('api.v1.auth.password.reset');
    });

    // Authenticated Routes
    Route::middleware(['auth:sanctum', 'ability:access-api'])->group(function () {
        Route::get('/me', \App\Http\Controllers\Api\V1\User\MeController::class)->name('api.v1.me');
        Route::put('/profile', \App\Http\Controllers\Api\V1\User\UpdateProfileController::class)->name('api.v1.user.profile.update');

        // Social/Community Features
        Route::post('/users/{user}/follow', \App\Http\Controllers\Api\V1\User\FollowUserController::class)->name('api.v1.users.follow');
        Route::post('/users/{user}/unfollow', \App\Http\Controllers\Api\V1\User\UnfollowUserController::class)->name('api.v1.users.unfollow');

        Route::post('/auth/logout', \App\Http\Controllers\Api\V1\Auth\LogoutController::class)->name('api.v1.auth.logout');

        // User Management
        Route::prefix('users')->middleware(['throttle:admin'])->group(function () {
            Route::get('/', \App\Http\Controllers\Api\V1\User\GetUsersController::class)->name('api.v1.users.index');
            Route::post('/', \App\Http\Controllers\Api\V1\User\CreateUserController::class)->name('api.v1.users.store');
            Route::get('/{user}', \App\Http\Controllers\Api\V1\User\ShowUserController::class)->name('api.v1.users.show');
            Route::put('/{user}', \App\Http\Controllers\Api\V1\User\UpdateUserController::class)->name('api.v1.users.update');
            Route::delete('/{user}', \App\Http\Controllers\Api\V1\User\DeleteUserController::class)->name('api.v1.users.destroy');
            Route::post('/{user}/ban', \App\Http\Controllers\Api\V1\User\BanUserController::class)->name('api.v1.users.ban');
            Route::post('/{user}/unban', \App\Http\Controllers\Api\V1\User\UnbanUserController::class)->name('api.v1.users.unban');
            Route::post('/{user}/block', \App\Http\Controllers\Api\V1\User\BlockUserController::class)->name('api.v1.users.block');
            Route::post('/{user}/unblock', \App\Http\Controllers\Api\V1\User\UnblockUserController::class)->name('api.v1.users.unblock');
        });

        // Article Management
        Route::prefix('articles')->group(function () {
            Route::post('/', \App\Http\Controllers\Api\V1\Article\CreateArticleController::class)->name('api.v1.articles.store');
            Route::post('/{article}/archive', \App\Http\Controllers\Api\V1\Article\ArchiveArticleController::class)->name('api.v1.articles.archive');
            Route::post('/{article}/restore', \App\Http\Controllers\Api\V1\Article\RestoreArticleController::class)->name('api.v1.articles.restore');
            Route::post('/{article}/trash', \App\Http\Controllers\Api\V1\Article\TrashArticleController::class)->name('api.v1.articles.trash');
            Route::post('/{article}/restore-from-trash', \App\Http\Controllers\Api\V1\Article\RestoreFromTrashController::class)->name('api.v1.articles.restore-from-trash');
            Route::post('/{article}/report', \App\Http\Controllers\Api\V1\Article\ReportArticleController::class)->name('api.v1.articles.report');
            Route::post('/{article}/approve', \App\Http\Controllers\Api\V1\Article\ApproveArticleController::class)->name('api.v1.articles.approve');
            Route::post('/{article}/pin', \App\Http\Controllers\Api\V1\Article\PinArticleController::class)->name('api.v1.articles.pin');
            Route::post('/{article}/unpin', \App\Http\Controllers\Api\V1\Article\UnpinArticleController::class)->name('api.v1.articles.unpin');
            Route::post('/{article}/feature', \App\Http\Controllers\Api\V1\Article\FeatureArticleController::class)->name('api.v1.articles.feature');
        });

        // Comment Management
        Route::prefix('articles')->group(function () {
            Route::post('/{article}/comments', \App\Http\Controllers\Api\V1\Comment\CreateCommentController::class)->name('api.v1.comments.store');
        });
        Route::prefix('comments')->middleware(['throttle:sensitive'])->group(function () {
            Route::get('/', \App\Http\Controllers\Api\V1\Comment\GetCommentsController::class)->name('api.v1.comments.index');
            Route::get('/own', \App\Http\Controllers\Api\V1\Comment\GetOwnCommentsController::class)->name('api.v1.comments.own');
            Route::put('/{comment}', \App\Http\Controllers\Api\V1\Comment\UpdateCommentController::class)->name('api.v1.comments.update');
            Route::delete('/{comment}', \App\Http\Controllers\Api\V1\Comment\DeleteCommentController::class)->name('api.v1.comments.destroy');
            Route::post('/{comment}/approve', \App\Http\Controllers\Api\V1\Comment\ApproveCommentController::class)->name('api.v1.comments.approve');
            Route::post('/{comment}/report', \App\Http\Controllers\Api\V1\Comment\ReportCommentController::class)->name('api.v1.comments.report');
        });

        // Newsletter Management
        Route::prefix('newsletter')->middleware(['throttle:admin'])->group(function () {
            Route::get('/subscribers', \App\Http\Controllers\Api\V1\Newsletter\GetSubscribersController::class)->name('api.v1.newsletter.subscribers.index');
            Route::delete('/subscribers/{newsletterSubscriber}', \App\Http\Controllers\Api\V1\Newsletter\DeleteSubscriberController::class)->name('api.v1.newsletter.subscribers.destroy');
        });

        // Notification Management
        Route::prefix('notifications')->group(function () {
            Route::get('/', \App\Http\Controllers\Api\V1\User\Notification\GetUserNotificationsController::class)->name('api.v1.user.notifications.index');
            Route::get('/unread-count', \App\Http\Controllers\Api\V1\User\Notification\GetUnreadNotificationsCountController::class)->name('api.v1.user.notifications.unread-count');
            Route::post('/mark-all-read', \App\Http\Controllers\Api\V1\User\Notification\MarkAllNotificationsAsReadController::class)->name('api.v1.user.notifications.mark-all-read');
            Route::post('/{userNotification}/mark-read', \App\Http\Controllers\Api\V1\User\Notification\MarkNotificationAsReadController::class)->name('api.v1.user.notifications.mark-read');
            Route::delete('/{userNotification}', \App\Http\Controllers\Api\V1\User\Notification\DeleteNotificationController::class)->name('api.v1.user.notifications.destroy');
        });
        Route::prefix('notifications')->middleware(['throttle:admin'])->group(function () {
            Route::get('/management', \App\Http\Controllers\Api\V1\Notification\GetNotificationsController::class)->name('api.v1.notifications.index');
            Route::post('/management', \App\Http\Controllers\Api\V1\Notification\CreateNotificationController::class)->name('api.v1.notifications.store');
        });

        // Taxonomy Management
        Route::prefix('categories')->middleware(['throttle:admin'])->group(function () {
            Route::post('/', \App\Http\Controllers\Api\V1\Category\CreateCategoryController::class)->name('api.v1.categories.store');
            Route::put('/{category}', \App\Http\Controllers\Api\V1\Category\UpdateCategoryController::class)->name('api.v1.categories.update');
            Route::delete('/{category}', \App\Http\Controllers\Api\V1\Category\DeleteCategoryController::class)->name('api.v1.categories.destroy');
        });

        Route::prefix('tags')->middleware(['throttle:admin'])->group(function () {
            Route::post('/', \App\Http\Controllers\Api\V1\Tag\CreateTagController::class)->name('api.v1.tags.store');
            Route::put('/{tag}', \App\Http\Controllers\Api\V1\Tag\UpdateTagController::class)->name('api.v1.tags.update');
            Route::delete('/{tag}', \App\Http\Controllers\Api\V1\Tag\DeleteTagController::class)->name('api.v1.tags.destroy');
        });

        // Media Management
        Route::prefix('media')->group(function () {
            Route::post('/', \App\Http\Controllers\Api\V1\Media\UploadMediaController::class)->name('api.v1.media.store');
            Route::get('/', \App\Http\Controllers\Api\V1\Media\GetMediaLibraryController::class)->name('api.v1.media.index');
            Route::get('/{media}', \App\Http\Controllers\Api\V1\Media\GetMediaDetailsController::class)->name('api.v1.media.show');
            Route::put('/{media}', \App\Http\Controllers\Api\V1\Media\UpdateMediaMetadataController::class)->name('api.v1.media.update');
            Route::delete('/{media}', \App\Http\Controllers\Api\V1\Media\DeleteMediaController::class)->name('api.v1.media.destroy');
        });
    });

    // Public Routes
    Route::middleware(['optional.sanctum'])->group(function () {
        // Article Routes
        Route::prefix('articles')->group(function () {
            Route::get('/', \App\Http\Controllers\Api\V1\Article\GetArticlesController::class)->name('api.v1.articles.index');
            Route::get('/{slug}', \App\Http\Controllers\Api\V1\Article\ShowArticleController::class)->name('api.v1.articles.show');
            Route::get('/{article:slug}/comments', \App\Http\Controllers\Api\V1\Article\GetCommentsController::class)->name('api.v1.articles.comments.index');
            Route::post('/{article:slug}/like', \App\Http\Controllers\Api\V1\Article\LikeArticleController::class)->name('api.v1.articles.like');
            Route::post('/{article:slug}/dislike', \App\Http\Controllers\Api\V1\Article\DislikeArticleController::class)->name('api.v1.articles.dislike');
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

        // Social/Community Features (Public)
        Route::prefix('users')->group(function () {
            Route::get('/{user}/followers', \App\Http\Controllers\Api\V1\User\GetUserFollowersController::class)->name('api.v1.users.followers');
            Route::get('/{user}/following', \App\Http\Controllers\Api\V1\User\GetUserFollowingController::class)->name('api.v1.users.following');
            Route::get('/{user}/profile', \App\Http\Controllers\Api\V1\User\ViewUserProfileController::class)->name('api.v1.users.profile');
        });
    });

});
