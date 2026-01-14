<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;

describe('ArticleStatus Enum', function () {
    it('has all expected cases', function () {
        // Assert
        expect(ArticleStatus::cases())->toHaveCount(6);
        expect(ArticleStatus::DRAFT)->toBeInstanceOf(ArticleStatus::class);
        expect(ArticleStatus::REVIEW)->toBeInstanceOf(ArticleStatus::class);
        expect(ArticleStatus::SCHEDULED)->toBeInstanceOf(ArticleStatus::class);
        expect(ArticleStatus::PUBLISHED)->toBeInstanceOf(ArticleStatus::class);
        expect(ArticleStatus::ARCHIVED)->toBeInstanceOf(ArticleStatus::class);
        expect(ArticleStatus::TRASHED)->toBeInstanceOf(ArticleStatus::class);
    });

    it('has correct string values', function () {
        // Assert
        expect(ArticleStatus::DRAFT->value)->toBe('draft');
        expect(ArticleStatus::REVIEW->value)->toBe('review');
        expect(ArticleStatus::SCHEDULED->value)->toBe('scheduled');
        expect(ArticleStatus::PUBLISHED->value)->toBe('published');
        expect(ArticleStatus::ARCHIVED->value)->toBe('archived');
        expect(ArticleStatus::TRASHED->value)->toBe('trashed');
    });

    it('can be created from string value', function () {
        // Act & Assert
        expect(ArticleStatus::from('draft'))->toBe(ArticleStatus::DRAFT);
        expect(ArticleStatus::from('review'))->toBe(ArticleStatus::REVIEW);
        expect(ArticleStatus::from('scheduled'))->toBe(ArticleStatus::SCHEDULED);
        expect(ArticleStatus::from('published'))->toBe(ArticleStatus::PUBLISHED);
        expect(ArticleStatus::from('archived'))->toBe(ArticleStatus::ARCHIVED);
        expect(ArticleStatus::from('trashed'))->toBe(ArticleStatus::TRASHED);
    });

    it('can be created from string value using tryFrom', function () {
        // Act & Assert
        expect(ArticleStatus::tryFrom('draft'))->toBe(ArticleStatus::DRAFT);
        expect(ArticleStatus::tryFrom('published'))->toBe(ArticleStatus::PUBLISHED);
        expect(ArticleStatus::tryFrom('invalid'))->toBeNull();
    });

    it('isPublished returns true for PUBLISHED', function () {
        // Assert
        expect(ArticleStatus::PUBLISHED->isPublished())->toBeTrue();
    });

    it('isPublished returns true for SCHEDULED', function () {
        // Assert
        expect(ArticleStatus::SCHEDULED->isPublished())->toBeTrue();
    });

    it('isPublished returns false for DRAFT', function () {
        // Assert
        expect(ArticleStatus::DRAFT->isPublished())->toBeFalse();
    });

    it('isPublished returns false for REVIEW', function () {
        // Assert
        expect(ArticleStatus::REVIEW->isPublished())->toBeFalse();
    });

    it('isPublished returns false for ARCHIVED', function () {
        // Assert
        expect(ArticleStatus::ARCHIVED->isPublished())->toBeFalse();
    });

    it('isPublished returns false for TRASHED', function () {
        // Assert
        expect(ArticleStatus::TRASHED->isPublished())->toBeFalse();
    });

    it('isDraft returns true for DRAFT', function () {
        // Assert
        expect(ArticleStatus::DRAFT->isDraft())->toBeTrue();
    });

    it('isDraft returns true for REVIEW', function () {
        // Assert
        expect(ArticleStatus::REVIEW->isDraft())->toBeTrue();
    });

    it('isDraft returns false for PUBLISHED', function () {
        // Assert
        expect(ArticleStatus::PUBLISHED->isDraft())->toBeFalse();
    });

    it('isDraft returns false for SCHEDULED', function () {
        // Assert
        expect(ArticleStatus::SCHEDULED->isDraft())->toBeFalse();
    });

    it('isDraft returns false for ARCHIVED', function () {
        // Assert
        expect(ArticleStatus::ARCHIVED->isDraft())->toBeFalse();
    });

    it('isDraft returns false for TRASHED', function () {
        // Assert
        expect(ArticleStatus::TRASHED->isDraft())->toBeFalse();
    });

    it('can be serialized to JSON', function () {
        // Act
        $json = json_encode(ArticleStatus::PUBLISHED);

        // Assert
        expect($json)->toBe('"published"');
    });
});
