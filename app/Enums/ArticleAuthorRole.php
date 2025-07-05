<?php

declare(strict_types=1);

namespace App\Enums;

enum ArticleAuthorRole: string
{
    case MAIN = 'main';
    case CO_AUTHOR = 'co_author';
    case CONTRIBUTOR = 'contributor';
}
