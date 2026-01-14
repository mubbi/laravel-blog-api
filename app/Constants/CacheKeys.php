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

    // Article cache keys
    public const ARTICLE_BY_SLUG_KEY = 'article:slug:';

    public const ARTICLE_BY_ID_KEY = 'article:id:';

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

    /**
     * Get article by slug cache key
     */
    public static function articleBySlug(string $slug): string
    {
        return self::ARTICLE_BY_SLUG_KEY.$slug;
    }

    /**
     * Get article by ID cache key
     */
    public static function articleById(int $id): string
    {
        return self::ARTICLE_BY_ID_KEY.$id;
    }
}
