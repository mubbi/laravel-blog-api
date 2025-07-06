<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
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
        \App\Models\Article::class => \App\Policies\ArticlePolicy::class,
        \App\Models\Comment::class => \App\Policies\CommentPolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Category::class => \App\Policies\CategoryPolicy::class,
        \App\Models\Tag::class => \App\Policies\TagPolicy::class,
        \App\Models\NewsletterSubscriber::class => \App\Policies\NewsletterSubscriberPolicy::class,
        \App\Models\Notification::class => \App\Policies\NotificationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Services\Interfaces\AuthServiceInterface::class,
            \App\Services\Auth\AuthService::class
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
                    return \App\Models\Permission::pluck('name')->toArray();
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
        } catch (\Exception $e) {
            // Database not ready or connection failed, skip permission gates
            // This can happen during initial setup or when database is not available
        }
    }
}
