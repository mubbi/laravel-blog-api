<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Cache key enumeration for application cache management
 */
enum CacheKey: string
{
    case TAGS = 'tags:list';
    case CATEGORIES = 'categories:list';
}
