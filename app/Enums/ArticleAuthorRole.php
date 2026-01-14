<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Article author role enumeration
 *
 * Defines the different roles an author can have when contributing to an article.
 */
enum ArticleAuthorRole: string
{
    case MAIN = 'main';
    case CO_AUTHOR = 'co_author';
    case CONTRIBUTOR = 'contributor';
}
