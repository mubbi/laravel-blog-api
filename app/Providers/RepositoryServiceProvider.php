<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Repositories\Contracts\NewsletterSubscriberRepositoryInterface;
use App\Repositories\Contracts\NotificationAudienceRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\TagRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\EloquentArticleRepository;
use App\Repositories\Eloquent\EloquentCategoryRepository;
use App\Repositories\Eloquent\EloquentCommentRepository;
use App\Repositories\Eloquent\EloquentMediaRepository;
use App\Repositories\Eloquent\EloquentNewsletterSubscriberRepository;
use App\Repositories\Eloquent\EloquentNotificationAudienceRepository;
use App\Repositories\Eloquent\EloquentNotificationRepository;
use App\Repositories\Eloquent\EloquentPermissionRepository;
use App\Repositories\Eloquent\EloquentRoleRepository;
use App\Repositories\Eloquent\EloquentTagRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider
 *
 * Responsible for binding repository interfaces to their Eloquent implementations.
 * This follows the Dependency Inversion Principle by allowing services to depend
 * on abstractions (interfaces) rather than concrete implementations.
 *
 * All repositories are stateless (only readonly dependencies), so we use
 * scoped() binding for optimal performance and safety:
 * - One instance per request/queue job lifecycle (shared within scope)
 * - Explicit isolation between requests/jobs (prevents state leakage)
 * - Better than singleton() for queue jobs and long-running processes
 * - Same performance as singleton() for HTTP requests
 */
final class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register repository bindings.
     *
     * Using scoped() for all stateless repositories:
     * - Reduces object creation overhead (reuses within scope)
     * - Saves memory by reusing instances within request/job
     * - Faster dependency resolution
     * - Explicit per-request/job isolation (safe for queue jobs)
     * - Future-proof: works correctly when repositories are used in queue jobs
     */
    public function register(): void
    {
        // User repositories
        $this->app->scoped(
            UserRepositoryInterface::class,
            EloquentUserRepository::class
        );

        // Content repositories
        $this->app->scoped(
            ArticleRepositoryInterface::class,
            EloquentArticleRepository::class
        );

        $this->app->scoped(
            CommentRepositoryInterface::class,
            EloquentCommentRepository::class
        );

        $this->app->scoped(
            CategoryRepositoryInterface::class,
            EloquentCategoryRepository::class
        );

        $this->app->scoped(
            TagRepositoryInterface::class,
            EloquentTagRepository::class
        );

        $this->app->scoped(
            MediaRepositoryInterface::class,
            EloquentMediaRepository::class
        );

        // Communication repositories
        $this->app->scoped(
            NotificationRepositoryInterface::class,
            EloquentNotificationRepository::class
        );

        $this->app->scoped(
            NewsletterSubscriberRepositoryInterface::class,
            EloquentNewsletterSubscriberRepository::class
        );

        $this->app->scoped(
            NotificationAudienceRepositoryInterface::class,
            EloquentNotificationAudienceRepository::class
        );

        // Authorization repositories
        $this->app->scoped(
            RoleRepositoryInterface::class,
            EloquentRoleRepository::class
        );

        $this->app->scoped(
            PermissionRepositoryInterface::class,
            EloquentPermissionRepository::class
        );
    }
}
