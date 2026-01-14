<?php

declare(strict_types=1);

use App\Enums\ArticleAuthorRole;

describe('ArticleAuthorRole Enum', function () {
    it('has all expected cases', function () {
        // Assert
        expect(ArticleAuthorRole::cases())->toHaveCount(3);
        expect(ArticleAuthorRole::MAIN)->toBeInstanceOf(ArticleAuthorRole::class);
        expect(ArticleAuthorRole::CO_AUTHOR)->toBeInstanceOf(ArticleAuthorRole::class);
        expect(ArticleAuthorRole::CONTRIBUTOR)->toBeInstanceOf(ArticleAuthorRole::class);
    });

    it('has correct string values', function () {
        // Assert
        expect(ArticleAuthorRole::MAIN->value)->toBe('main');
        expect(ArticleAuthorRole::CO_AUTHOR->value)->toBe('co_author');
        expect(ArticleAuthorRole::CONTRIBUTOR->value)->toBe('contributor');
    });

    it('can be created from string value', function () {
        // Act & Assert
        expect(ArticleAuthorRole::from('main'))->toBe(ArticleAuthorRole::MAIN);
        expect(ArticleAuthorRole::from('co_author'))->toBe(ArticleAuthorRole::CO_AUTHOR);
        expect(ArticleAuthorRole::from('contributor'))->toBe(ArticleAuthorRole::CONTRIBUTOR);
    });

    it('can be created from string value using tryFrom', function () {
        // Act & Assert
        expect(ArticleAuthorRole::tryFrom('main'))->toBe(ArticleAuthorRole::MAIN);
        expect(ArticleAuthorRole::tryFrom('co_author'))->toBe(ArticleAuthorRole::CO_AUTHOR);
        expect(ArticleAuthorRole::tryFrom('contributor'))->toBe(ArticleAuthorRole::CONTRIBUTOR);
        expect(ArticleAuthorRole::tryFrom('invalid'))->toBeNull();
    });

    it('can be serialized to JSON', function () {
        // Act
        $json = json_encode(ArticleAuthorRole::MAIN);

        // Assert
        expect($json)->toBe('"main"');
    });

    it('can be used in match expressions', function () {
        // Act
        $result = match (ArticleAuthorRole::MAIN) {
            ArticleAuthorRole::MAIN => 'main_role',
            ArticleAuthorRole::CO_AUTHOR => 'co_author_role',
            ArticleAuthorRole::CONTRIBUTOR => 'contributor_role',
        };

        // Assert
        expect($result)->toBe('main_role');
    });
});
