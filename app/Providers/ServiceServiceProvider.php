<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ArticleFeatureService;
use App\Services\ArticleManagementService;
use App\Services\ArticleReportService;
use App\Services\ArticleService;
use App\Services\ArticleStatusService;
use App\Services\CacheService;
use App\Services\CategoryService;
use App\Services\CommentService;
use App\Services\Interfaces\ArticleFeatureServiceInterface;
use App\Services\Interfaces\ArticleManagementServiceInterface;
use App\Services\Interfaces\ArticleReportServiceInterface;
use App\Services\Interfaces\ArticleServiceInterface;
use App\Services\Interfaces\ArticleStatusServiceInterface;
use App\Services\Interfaces\CacheServiceInterface;
use App\Services\Interfaces\CategoryServiceInterface;
use App\Services\Interfaces\CommentServiceInterface;
use App\Services\Interfaces\NewsletterServiceInterface;
use App\Services\Interfaces\NotificationServiceInterface;
use App\Services\Interfaces\TagServiceInterface;
use App\Services\Interfaces\UserServiceInterface;
use App\Services\NewsletterService;
use App\Services\NotificationService;
use App\Services\TagService;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;

/**
 * Service Service Provider
 *
 * Responsible for binding service interfaces to their implementations.
 * This follows the Dependency Inversion Principle by allowing controllers
 * and other services to depend on abstractions (interfaces) rather than
 * concrete implementations.
 *
 * All services are stateless (only readonly dependencies), so we use
 * scoped() binding for optimal performance and safety:
 * - One instance per request/queue job lifecycle (shared within scope)
 * - Explicit isolation between requests/jobs (prevents state leakage)
 * - Better than singleton() for queue jobs and long-running processes
 * - Same performance as singleton() for HTTP requests
 */
final class ServiceServiceProvider extends ServiceProvider
{
    /**
     * Register service bindings.
     *
     * Using scoped() for all stateless services:
     * - Reduces object creation overhead (reuses within scope)
     * - Saves memory by reusing instances within request/job
     * - Faster dependency resolution
     * - Explicit per-request/job isolation (safe for queue jobs)
     * - Future-proof: works correctly when services are used in queue jobs
     */
    public function register(): void
    {
        // User management services
        $this->app->scoped(
            UserServiceInterface::class,
            UserService::class
        );

        // Article services
        $this->app->scoped(
            ArticleServiceInterface::class,
            ArticleService::class
        );

        // Article query builder and filter services (internal use)
        // These are concrete classes that Laravel can auto-resolve, so no explicit binding needed

        $this->app->scoped(
            ArticleManagementServiceInterface::class,
            ArticleManagementService::class
        );

        $this->app->scoped(
            ArticleStatusServiceInterface::class,
            ArticleStatusService::class
        );

        $this->app->scoped(
            ArticleFeatureServiceInterface::class,
            ArticleFeatureService::class
        );

        $this->app->scoped(
            ArticleReportServiceInterface::class,
            ArticleReportService::class
        );

        // Content services
        $this->app->scoped(
            CategoryServiceInterface::class,
            CategoryService::class
        );

        $this->app->scoped(
            TagServiceInterface::class,
            TagService::class
        );

        $this->app->scoped(
            CommentServiceInterface::class,
            CommentService::class
        );

        // Communication services
        $this->app->scoped(
            NewsletterServiceInterface::class,
            NewsletterService::class
        );

        $this->app->scoped(
            NotificationServiceInterface::class,
            NotificationService::class
        );

        // Utility services
        $this->app->scoped(
            CacheServiceInterface::class,
            CacheService::class
        );
    }
}
