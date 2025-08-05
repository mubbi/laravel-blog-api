<?php

declare(strict_types=1);

namespace App\Enums;

enum CommentStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case SPAM = 'spam';

    /**
     * Get the display name for the status
     */
    public function displayName(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::SPAM => 'Spam',
        };
    }

    /**
     * Check if the status is a published state
     */
    public function isPublished(): bool
    {
        return $this === self::APPROVED;
    }

    /**
     * Check if the status is a draft state
     */
    public function isDraft(): bool
    {
        return in_array($this, [self::PENDING, self::REJECTED]);
    }
}
