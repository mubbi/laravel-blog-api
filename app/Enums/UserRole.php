<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case ADMINISTRATOR = 'Administrator';
    case EDITOR = 'Editor';
    case AUTHOR = 'Author';
    case CONTRIBUTOR = 'Contributor';
    case SUBSCRIBER = 'Subscriber';
}
