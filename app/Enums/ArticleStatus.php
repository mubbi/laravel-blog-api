<?php

declare(strict_types=1);

namespace App\Enums;

enum ArticleStatus: string
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
}
