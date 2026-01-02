<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\NewsletterSubscriber;
use App\Models\Notification;
use App\Models\Permission;
use App\Models\Tag;
use App\Models\User;
use App\Policies\ArticlePolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CommentPolicy;
use App\Policies\NewsletterSubscriberPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\TagPolicy;
use App\Policies\UserPolicy;
use App\Services\Auth\AuthService;
use App\Services\Interfaces\AuthServiceInterface;
use Exception;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Article::class => ArticlePolicy::class,
        Comment::class => CommentPolicy::class,
        User::class => UserPolicy::class,
        Category::class => CategoryPolicy::class,
        Tag::class => TagPolicy::class,
        NewsletterSubscriber::class => NewsletterSubscriberPolicy::class,
        Notification::class => NotificationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function register(): void
    {
        $this->app->bind(
            AuthServiceInterface::class,
            AuthService::class
        );
    }

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Build a list of permissions already handled by policies
        $policyPermissions = [];
        foreach ($this->policies as $model => $policy) {
            if (method_exists($policy, 'permissions')) {
                $perms = $policy::permissions();
                if (is_array($perms)) {
                    $policyPermissions = array_merge($policyPermissions, $perms);
                }
            }
        }

        // Dynamically register gates for all permissions in the database
        // Only proceed if the permissions table exists and database is accessible
        try {
            if (Schema::hasTable('permissions')) {
                // Cache permissions for 1 hour to avoid repeated DB hits
                $permissions = cache()->remember('all_permissions', 3600, function () {
                    return Permission::pluck('name')->toArray();
                });

                foreach ($permissions as $permission) {
                    if (! is_string($permission) || isset($policyPermissions[$permission])) {
                        continue;
                    }
                    Gate::define($permission, function (User $user) use ($permission) {
                        // Eager load roles and permissions to avoid N+1 queries
                        if (! $user->relationLoaded('roles')) {
                            $user->loadMissing('roles.permissions');
                        }
                        // If user has no roles, return false immediately
                        if (count($user->roles) === 0) {
                            return false;
                        }
                        // Eager load permissions for all roles
                        foreach ($user->roles as $role) {
                            if (! $role->relationLoaded('permissions')) {
                                $user->loadMissing('roles.permissions');
                                break;
                            }
                        }

                        return $user->hasPermission($permission);
                    });
                }
            }
        } catch (Exception $e) {
            // Database not ready or connection failed, skip permission gates
            // This can happen during initial setup or when database is not available
        }
    }
}
