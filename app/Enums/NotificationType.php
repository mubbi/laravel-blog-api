<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Notification type enumeration
 *
 * Defines the different types of notifications that can be sent to users.
 */
enum NotificationType: string
{
    case ARTICLE_PUBLISHED = 'article_published';
    case NEW_COMMENT = 'new_comment';
    case NEWSLETTER = 'newsletter';
    case SYSTEM_ALERT = 'system_alert';
}
