<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Cache key enumeration for application cache management
 * Use this enum for static cache keys. For dynamic keys (with IDs/slugs), use CacheKeys class.
 */
enum CacheKey: string
{
    case TAGS = 'tags:list';
    case CATEGORIES = 'categories:list';
    case ARTICLE_BY_SLUG = 'article:slug:'; // Prefix - append slug
    case ARTICLE_BY_ID = 'article:id:'; // Prefix - append ID
    case USER_ROLES = 'user_roles_'; // Prefix - append user ID
    case USER_PERMISSIONS = 'user_permissions_'; // Prefix - append user ID
    case ALL_ROLES = 'all_roles_with_permissions';
    case ALL_PERMISSIONS = 'all_permissions';
}
