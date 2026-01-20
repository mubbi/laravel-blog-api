<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Cache TTL
    |--------------------------------------------------------------------------
    |
    | This is the default time-to-live (TTL) in seconds for cache entries
    | that don't have a specific TTL defined. Default is 24 hours (86400 seconds).
    |
    */

    'default' => env('CACHE_DEFAULT_TTL', 86400), // 24 hours

    /*
    |--------------------------------------------------------------------------
    | Cache Key Specific TTLs
    |--------------------------------------------------------------------------
    |
    | Here you may define specific TTL values for individual cache keys.
    | If a key is not defined here, the default TTL will be used.
    |
    | Format: 'cache_key' => TTL in seconds
    |
    */

    'keys' => [
        'tags:list' => env('CACHE_TTL_TAGS', 86400), // 24 hours
        'categories:list' => env('CACHE_TTL_CATEGORIES', 86400), // 24 hours
        'article_by_slug' => env('CACHE_TTL_ARTICLE_BY_SLUG', 3600), // 1 hour
        'article_by_id' => env('CACHE_TTL_ARTICLE_BY_ID', 3600), // 1 hour
        'user_roles' => env('CACHE_TTL_USER_ROLES', 3600), // 1 hour
        'user_permissions' => env('CACHE_TTL_USER_PERMISSIONS', 3600), // 1 hour
        'all_roles_with_permissions' => env('CACHE_TTL_ALL_ROLES', 3600), // 1 hour
        'all_permissions' => env('CACHE_TTL_ALL_PERMISSIONS', 3600), // 1 hour
    ],
];
