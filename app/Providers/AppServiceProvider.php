<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\Contracts\NewsletterSubscriberRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\TagRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\EloquentArticleRepository;
use App\Repositories\Eloquent\EloquentCategoryRepository;
use App\Repositories\Eloquent\EloquentCommentRepository;
use App\Repositories\Eloquent\EloquentNewsletterSubscriberRepository;
use App\Repositories\Eloquent\EloquentNotificationRepository;
use App\Repositories\Eloquent\EloquentTagRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Support\Helper;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(
            UserRepositoryInterface::class,
            EloquentUserRepository::class
        );

        $this->app->bind(
            ArticleRepositoryInterface::class,
            EloquentArticleRepository::class
        );

        $this->app->bind(
            CommentRepositoryInterface::class,
            EloquentCommentRepository::class
        );

        $this->app->bind(
            CategoryRepositoryInterface::class,
            EloquentCategoryRepository::class
        );

        $this->app->bind(
            TagRepositoryInterface::class,
            EloquentTagRepository::class
        );

        $this->app->bind(
            NotificationRepositoryInterface::class,
            EloquentNotificationRepository::class
        );

        $this->app->bind(
            NewsletterSubscriberRepositoryInterface::class,
            EloquentNewsletterSubscriberRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Date::use(CarbonImmutable::class);
        Model::shouldBeStrict(! $this->app->isProduction());
        DB::prohibitDestructiveCommands($this->app->isProduction());

        // Disable rate limiting during testing
        if ($this->app->environment('testing')) {
            RateLimiter::for('api', fn () => Limit::none());
        } else {
            // Rate Limiting for API routes
            RateLimiter::for('api', function (Request $request) {
                $rateLimit = config('rate-limiting.api.default_rate_limit');
                $rateLimitInt = is_numeric($rateLimit) ? (int) $rateLimit : 60;

                return Limit::perMinute($rateLimitInt)
                    ->by($request->user()?->id ?: Helper::getRealIpAddress($request));
            });
        }
    }
}
