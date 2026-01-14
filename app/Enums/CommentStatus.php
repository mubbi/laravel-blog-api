<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Comment status enumeration
 *
 * Defines the various states a comment can be in during the moderation process.
 */
enum CommentStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case SPAM = 'spam';

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
