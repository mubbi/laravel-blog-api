<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Article status enumeration
 *
 * Defines the various states an article can be in throughout its lifecycle.
 */
enum ArticleStatus: string
{
    case DRAFT = 'draft';
    case REVIEW = 'review';
    case SCHEDULED = 'scheduled';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
    case TRASHED = 'trashed';

    /**
     * Check if the status is a published state
     */
    public function isPublished(): bool
    {
        return in_array($this, [self::PUBLISHED, self::SCHEDULED]);
    }

    /**
     * Check if the status is a draft state
     */
    public function isDraft(): bool
    {
        return in_array($this, [self::DRAFT, self::REVIEW]);
    }
}
