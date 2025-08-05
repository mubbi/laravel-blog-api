<?php

declare(strict_types=1);

namespace App\Traits;

use App\Constants\CacheKeys;
use Illuminate\Support\Facades\Cache;

trait HasCachedRolesAndPermissions
{
    /**
     * Get cached permissions for the user
     *
     * @return array<int, string>
     */
    public function getCachedPermissions(): array
    {
        $cacheVersion = $this->getCacheVersion();
        $cacheKey = CacheKeys::userPermissions($this->id).'_v'.$cacheVersion;

        /** @var array<int, string> $result */
        $result = Cache::remember($cacheKey, CacheKeys::CACHE_TTL, function () {
            $this->load('roles.permissions');

            return $this->extractPermissionsFromRoles();
        });

        return $result;
    }

    /**
     * Get cached roles for the user
     *
     * @return array<int, string>
     */
    public function getCachedRoles(): array
    {
        $cacheVersion = $this->getCacheVersion();
        $cacheKey = CacheKeys::userRoles($this->id).'_v'.$cacheVersion;

        /** @var array<int, string> $result */
        $result = Cache::remember($cacheKey, CacheKeys::CACHE_TTL, function () {
            return $this->roles->pluck('name')->toArray();
        });

        return $result;
    }

    /**
     * Check if the user has a given permission using cache
     */
    public function hasCachedPermission(string $permission): bool
    {
        $permissions = $this->getCachedPermissions();

        return in_array($permission, $permissions, true);
    }

    /**
     * Check if the user has any of the given permissions using cache
     *
     * @param  array<int, string>  $permissions
     */
    public function hasAnyCachedPermission(array $permissions): bool
    {
        $userPermissions = $this->getCachedPermissions();

        return ! empty(array_intersect($permissions, $userPermissions));
    }

    /**
     * Check if the user has all of the given permissions using cache
     *
     * @param  array<int, string>  $permissions
     */
    public function hasAllCachedPermissions(array $permissions): bool
    {
        $userPermissions = $this->getCachedPermissions();

        return empty(array_diff($permissions, $userPermissions));
    }

    /**
     * Check if the user has a given role using cache
     */
    public function hasCachedRole(string $role): bool
    {
        $roles = $this->getCachedRoles();

        return in_array($role, $roles, true);
    }

    /**
     * Check if the user has any of the given roles using cache
     *
     * @param  array<int, string>  $roles
     */
    public function hasAnyCachedRole(array $roles): bool
    {
        $userRoles = $this->getCachedRoles();

        return ! empty(array_intersect($roles, $userRoles));
    }

    /**
     * Check if the user has all of the given roles using cache
     *
     * @param  array<int, string>  $roles
     */
    public function hasAllCachedRoles(array $roles): bool
    {
        $userRoles = $this->getCachedRoles();

        return empty(array_diff($roles, $userRoles));
    }

    /**
     * Clear user's cached permissions and roles
     */
    public function clearCache(): void
    {
        // Clear individual user cache (for specific user updates)
        $cacheVersion = $this->getCacheVersion();
        Cache::forget(CacheKeys::userPermissions($this->id).'_v'.$cacheVersion);
        Cache::forget(CacheKeys::userRoles($this->id).'_v'.$cacheVersion);
    }

    /**
     * Refresh user's cached permissions and roles
     */
    public function refreshCache(): void
    {
        $this->clearCache();
        $this->getCachedPermissions();
        $this->getCachedRoles();
    }

    /**
     * Get current cache version
     */
    private function getCacheVersion(): int
    {
        /** @var int $result */
        $result = Cache::get('user_cache_version', 1);

        return $result;
    }

    /**
     * Extract permissions from user's roles
     *
     * @return array<int, string>
     */
    private function extractPermissionsFromRoles(): array
    {
        $permissions = [];

        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                $permissions[] = $permission->name;
            }
        }

        return array_unique($permissions);
    }
}
