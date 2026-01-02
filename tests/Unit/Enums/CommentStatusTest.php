<?php

declare(strict_types=1);

use App\Enums\CommentStatus;

describe('CommentStatus Enum', function () {
    it('has all expected cases', function () {
        // Assert
        expect(CommentStatus::cases())->toHaveCount(4);
        expect(CommentStatus::PENDING)->toBeInstanceOf(CommentStatus::class);
        expect(CommentStatus::APPROVED)->toBeInstanceOf(CommentStatus::class);
        expect(CommentStatus::REJECTED)->toBeInstanceOf(CommentStatus::class);
        expect(CommentStatus::SPAM)->toBeInstanceOf(CommentStatus::class);
    });

    it('has correct string values', function () {
        // Assert
        expect(CommentStatus::PENDING->value)->toBe('pending');
        expect(CommentStatus::APPROVED->value)->toBe('approved');
        expect(CommentStatus::REJECTED->value)->toBe('rejected');
        expect(CommentStatus::SPAM->value)->toBe('spam');
    });

    it('can be created from string value', function () {
        // Act & Assert
        expect(CommentStatus::from('pending'))->toBe(CommentStatus::PENDING);
        expect(CommentStatus::from('approved'))->toBe(CommentStatus::APPROVED);
        expect(CommentStatus::from('rejected'))->toBe(CommentStatus::REJECTED);
        expect(CommentStatus::from('spam'))->toBe(CommentStatus::SPAM);
    });

    it('can be created from string value using tryFrom', function () {
        // Act & Assert
        expect(CommentStatus::tryFrom('pending'))->toBe(CommentStatus::PENDING);
        expect(CommentStatus::tryFrom('approved'))->toBe(CommentStatus::APPROVED);
        expect(CommentStatus::tryFrom('rejected'))->toBe(CommentStatus::REJECTED);
        expect(CommentStatus::tryFrom('spam'))->toBe(CommentStatus::SPAM);
        expect(CommentStatus::tryFrom('invalid'))->toBeNull();
    });

    it('isPublished returns true for APPROVED', function () {
        // Assert
        expect(CommentStatus::APPROVED->isPublished())->toBeTrue();
    });

    it('isPublished returns false for PENDING', function () {
        // Assert
        expect(CommentStatus::PENDING->isPublished())->toBeFalse();
    });

    it('isPublished returns false for REJECTED', function () {
        // Assert
        expect(CommentStatus::REJECTED->isPublished())->toBeFalse();
    });

    it('isPublished returns false for SPAM', function () {
        // Assert
        expect(CommentStatus::SPAM->isPublished())->toBeFalse();
    });

    it('isDraft returns true for PENDING', function () {
        // Assert
        expect(CommentStatus::PENDING->isDraft())->toBeTrue();
    });

    it('isDraft returns true for REJECTED', function () {
        // Assert
        expect(CommentStatus::REJECTED->isDraft())->toBeTrue();
    });

    it('isDraft returns false for APPROVED', function () {
        // Assert
        expect(CommentStatus::APPROVED->isDraft())->toBeFalse();
    });

    it('isDraft returns false for SPAM', function () {
        // Assert
        expect(CommentStatus::SPAM->isDraft())->toBeFalse();
    });

    it('can be serialized to JSON', function () {
        // Act
        $json = json_encode(CommentStatus::APPROVED);

        // Assert
        expect($json)->toBe('"approved"');
    });
});
