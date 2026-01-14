<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * User role enumeration
 *
 * Defines the different permission levels users can have in the system.
 */
enum UserRole: string
{
    case ADMINISTRATOR = 'administrator';
    case EDITOR = 'editor';
    case AUTHOR = 'author';
    case CONTRIBUTOR = 'contributor';
    case SUBSCRIBER = 'subscriber';

    /**
     * Get the display name for the role
     */
    public function displayName(): string
    {
        return match ($this) {
            self::ADMINISTRATOR => 'Administrator',
            self::EDITOR => 'Editor',
            self::AUTHOR => 'Author',
            self::CONTRIBUTOR => 'Contributor',
            self::SUBSCRIBER => 'Subscriber',
        };
    }
}
