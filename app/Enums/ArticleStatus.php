<?php

declare(strict_types=1);

namespace App\Enums;

enum ArticleStatus: string
{
    case DRAFT = 'draft';
    case REVIEW = 'review';
    case SCHEDULED = 'scheduled';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
    case TRASHED = 'trashed';

    /**
     * Get the display name for the status
     */
    public function displayName(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::REVIEW => 'Under Review',
            self::SCHEDULED => 'Scheduled',
            self::PUBLISHED => 'Published',
            self::ARCHIVED => 'Archived',
            self::TRASHED => 'Trashed',
        };
    }

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
