<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\CommentRepositoryInterface;
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
 */
final class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register repository bindings.
     */
    public function register(): void
    {
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

        $this->app->bind(
            RoleRepositoryInterface::class,
            EloquentRoleRepository::class
        );

        $this->app->bind(
            PermissionRepositoryInterface::class,
            EloquentPermissionRepository::class
        );

        $this->app->bind(
            NotificationAudienceRepositoryInterface::class,
            EloquentNotificationAudienceRepository::class
        );
    }
}
