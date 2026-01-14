<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Article reaction type enumeration
 *
 * Defines the types of reactions that can be applied to articles.
 */
enum ArticleReactionType: string
{
    case LIKE = 'like';
    case DISLIKE = 'dislike';

    /**
     * Get the opposite reaction type
     */
    public function opposite(): self
    {
        return match ($this) {
            self::LIKE => self::DISLIKE,
            self::DISLIKE => self::LIKE,
        };
    }
}
