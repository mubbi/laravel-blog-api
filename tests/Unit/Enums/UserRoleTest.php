<?php

declare(strict_types=1);

use App\Enums\UserRole;

describe('UserRole Enum', function () {
    it('has all expected cases', function () {
        // Assert
        expect(UserRole::cases())->toHaveCount(5);
        expect(UserRole::ADMINISTRATOR)->toBeInstanceOf(UserRole::class);
        expect(UserRole::EDITOR)->toBeInstanceOf(UserRole::class);
        expect(UserRole::AUTHOR)->toBeInstanceOf(UserRole::class);
        expect(UserRole::CONTRIBUTOR)->toBeInstanceOf(UserRole::class);
        expect(UserRole::SUBSCRIBER)->toBeInstanceOf(UserRole::class);
    });

    it('has correct string values', function () {
        // Assert
        expect(UserRole::ADMINISTRATOR->value)->toBe('administrator');
        expect(UserRole::EDITOR->value)->toBe('editor');
        expect(UserRole::AUTHOR->value)->toBe('author');
        expect(UserRole::CONTRIBUTOR->value)->toBe('contributor');
        expect(UserRole::SUBSCRIBER->value)->toBe('subscriber');
    });

    it('can be created from string value', function () {
        // Act & Assert
        expect(UserRole::from('administrator'))->toBe(UserRole::ADMINISTRATOR);
        expect(UserRole::from('editor'))->toBe(UserRole::EDITOR);
        expect(UserRole::from('author'))->toBe(UserRole::AUTHOR);
        expect(UserRole::from('contributor'))->toBe(UserRole::CONTRIBUTOR);
        expect(UserRole::from('subscriber'))->toBe(UserRole::SUBSCRIBER);
    });

    it('can be created from string value using tryFrom', function () {
        // Act & Assert
        expect(UserRole::tryFrom('administrator'))->toBe(UserRole::ADMINISTRATOR);
        expect(UserRole::tryFrom('editor'))->toBe(UserRole::EDITOR);
        expect(UserRole::tryFrom('author'))->toBe(UserRole::AUTHOR);
        expect(UserRole::tryFrom('contributor'))->toBe(UserRole::CONTRIBUTOR);
        expect(UserRole::tryFrom('subscriber'))->toBe(UserRole::SUBSCRIBER);
        expect(UserRole::tryFrom('invalid'))->toBeNull();
    });

    it('can be serialized to JSON', function () {
        // Act
        $json = json_encode(UserRole::ADMINISTRATOR);

        // Assert
        expect($json)->toBe('"administrator"');
    });

    it('can be used in match expressions', function () {
        // Act
        $result = match (UserRole::EDITOR) {
            UserRole::ADMINISTRATOR => 'admin',
            UserRole::EDITOR => 'edit',
            UserRole::AUTHOR => 'write',
            UserRole::CONTRIBUTOR => 'contribute',
            UserRole::SUBSCRIBER => 'read',
        };

        // Assert
        expect($result)->toBe('edit');
    });
});
