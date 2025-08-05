<?php

declare(strict_types=1);

namespace App\Constants;

final class CacheKeys
{
    public const CACHE_TTL = 3600; // 1 hour

    // User-specific cache keys
    public const USER_ROLES_CACHE_KEY = 'user_roles_';

    public const USER_PERMISSIONS_CACHE_KEY = 'user_permissions_';

    // Global cache keys
    public const ALL_ROLES_CACHE_KEY = 'all_roles_with_permissions';

    public const ALL_PERMISSIONS_CACHE_KEY = 'all_permissions';

    /**
     * Get user roles cache key
     */
    public static function userRoles(int $userId): string
    {
        return self::USER_ROLES_CACHE_KEY.$userId;
    }

    /**
     * Get user permissions cache key
     */
    public static function userPermissions(int $userId): string
    {
        return self::USER_PERMISSIONS_CACHE_KEY.$userId;
    }
}
