<?php

declare(strict_types=1);

namespace App\Models;

use App\Constants\CacheKeys;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Permission> $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 *
 * @mixin \Eloquent
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<self>
 */
final class Role extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Boot the model and register event listeners
     */
    protected static function boot(): void
    {
        parent::boot();

        // Clear user caches when role permissions are updated
        self::updated(function (Role $role) {
            $role->clearUserCaches();
        });

        // Clear user caches when role is deleted
        self::deleted(function (Role $role) {
            $role->clearUserCaches();
        });
    }

    /**
     * Clear caches for all users who have this role
     */
    private function clearUserCaches(): void
    {
        // Instead of clearing individual user caches, increment the cache version
        // This will invalidate all user caches at once
        $this->incrementCacheVersion();
    }

    /**
     * Increment cache version to invalidate all user caches
     */
    private function incrementCacheVersion(): void
    {
        /** @var int $currentVersion */
        $currentVersion = \Illuminate\Support\Facades\Cache::get('user_cache_version', 1);
        \Illuminate\Support\Facades\Cache::put('user_cache_version', $currentVersion + 1, CacheKeys::CACHE_TTL);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * The permissions that belong to the role.
     *
     * @return BelongsToMany<Permission, Role, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function permissions(): BelongsToMany
    {
        /** @var BelongsToMany<Permission, Role, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'> $relation */
        $relation = $this->belongsToMany(Permission::class);

        return $relation;
    }

    /**
     * The users that belong to the role.
     *
     * @return BelongsToMany<User, Role, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function users(): BelongsToMany
    {
        /** @var BelongsToMany<User, Role, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'> $relation */
        $relation = $this->belongsToMany(User::class);

        return $relation;
    }
}
