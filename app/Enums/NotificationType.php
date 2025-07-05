<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationType: string
{
    case ARTICLE_PUBLISHED = 'article_published';
    case NEW_COMMENT = 'new_comment';
    case NEWSLETTER = 'newsletter';
    case SYSTEM_ALERT = 'system_alert';
}
