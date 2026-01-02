<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCachedRolesAndPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;
use Throwable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property string|null $avatar_url
 * @property string|null $bio
 * @property string|null $twitter
 * @property string|null $facebook
 * @property string|null $linkedin
 * @property string|null $github
 * @property string|null $website
 * @property \Illuminate\Support\Carbon|null $banned_at
 * @property \Illuminate\Support\Carbon|null $blocked_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $token Dynamic property set by auth service
 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property (\Illuminate\Support\Carbon|\Carbon\CarbonImmutable)|null $access_token_expires_at
 * @property (\Illuminate\Support\Carbon|\Carbon\CarbonImmutable)|null $refresh_token_expires_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Role> $roles
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Article> $articles
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Comment> $comments
 *
 * @mixin \Eloquent
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<self>
 */
final class User extends Authenticatable
{
    use HasApiTokens, HasCachedRolesAndPermissions, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url',
        'bio',
        'twitter',
        'facebook',
        'linkedin',
        'github',
        'website',
        'banned_at',
        'blocked_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Boot the model and register event listeners
     */
    protected static function boot(): void
    {
        parent::boot();

        // Clear cache when user is updated
        self::updated(function (User $user) {
            $user->clearCache();
        });

        // Clear cache when user is deleted
        self::deleted(function (User $user) {
            $user->clearCache();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'banned_at' => 'datetime',
            'blocked_at' => 'datetime',
        ];
    }

    /**
     * The roles that belong to the user.
     *
     * @return BelongsToMany<Role, User, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function roles(): BelongsToMany
    {
        /** @var BelongsToMany<Role, User, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'> $relation */
        $relation = $this->belongsToMany(Role::class);

        return $relation;
    }

    /**
     * Check if the user has a given role.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles->pluck('name')->contains($role);
    }

    /**
     * Check if the user has any of the given roles.
     *
     * @param  array<int, string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        $userRoles = $this->roles->pluck('name')->toArray();

        return ! empty(array_intersect($roles, $userRoles));
    }

    /**
     * Check if the user has all of the given roles.
     *
     * @param  array<int, string>  $roles
     */
    public function hasAllRoles(array $roles): bool
    {
        $userRoles = $this->roles->pluck('name')->toArray();

        return empty(array_diff($roles, $userRoles));
    }

    /**
     * Check if the user has a given permission via their roles.
     */
    public function hasPermission(string $permission): bool
    {
        try {
            // Try to get from cache first (most efficient)
            if ($this->hasCachedPermission($permission)) {
                return true;
            }

            // If cache miss, check if roles are already loaded
            if ($this->relationLoaded('roles')) {
                foreach ($this->roles as $role) {
                    if ($role->relationLoaded('permissions')) {
                        if ($role->permissions->contains('name', $permission)) {
                            return true;
                        }
                    }
                }
            }

            // Fallback to database query with eager loading
            $this->load('roles.permissions');

            foreach ($this->roles as $role) {
                /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission> $permissions */
                $permissions = $role->permissions;
                if ($permissions->contains('name', $permission)) {
                    return true;
                }
            }

            return false;
        } catch (Throwable $e) {
            Log::error('hasPermission error', [
                'user_id' => $this->id,
                'permission' => $permission,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Check if the user has any of the given permissions.
     *
     * @param  array<int, string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        try {
            // Try to get from cache first
            if ($this->hasAnyCachedPermission($permissions)) {
                return true;
            }

            // Fallback to database query
            $this->load('roles.permissions');

            foreach ($this->roles as $role) {
                foreach ($role->permissions as $permission) {
                    if (in_array($permission->name, $permissions, true)) {
                        return true;
                    }
                }
            }

            return false;
        } catch (Throwable $e) {
            Log::error('hasAnyPermission error', [
                'user_id' => $this->id,
                'permissions' => $permissions,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if the user has all of the given permissions.
     *
     * @param  array<int, string>  $permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        try {
            // Try to get from cache first
            if ($this->hasAllCachedPermissions($permissions)) {
                return true;
            }

            // Fallback to database query
            $this->load('roles.permissions');

            $userPermissions = [];
            foreach ($this->roles as $role) {
                foreach ($role->permissions as $permission) {
                    $userPermissions[] = $permission->name;
                }
            }

            $userPermissions = array_unique($userPermissions);

            return empty(array_diff($permissions, $userPermissions));
        } catch (Throwable $e) {
            Log::error('hasAllPermissions error', [
                'user_id' => $this->id,
                'permissions' => $permissions,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get the articles created by the user.
     *
     * @return HasMany<Article, User>
     */
    public function articles(): HasMany
    {
        /** @var HasMany<Article, User> $relation */
        $relation = $this->hasMany(Article::class, 'created_by');

        return $relation;
    }

    /**
     * Get the comments created by the user.
     *
     * @return HasMany<Comment, User>
     */
    public function comments(): HasMany
    {
        /** @var HasMany<Comment, User> $relation */
        $relation = $this->hasMany(Comment::class, 'user_id');

        return $relation;
    }
}
