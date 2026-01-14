<?php

declare(strict_types=1);

use App\Enums\NotificationType;

describe('NotificationType Enum', function () {
    it('has all expected cases', function () {
        // Assert
        expect(NotificationType::cases())->toHaveCount(4);
        expect(NotificationType::ARTICLE_PUBLISHED)->toBeInstanceOf(NotificationType::class);
        expect(NotificationType::NEW_COMMENT)->toBeInstanceOf(NotificationType::class);
        expect(NotificationType::NEWSLETTER)->toBeInstanceOf(NotificationType::class);
        expect(NotificationType::SYSTEM_ALERT)->toBeInstanceOf(NotificationType::class);
    });

    it('has correct string values', function () {
        // Assert
        expect(NotificationType::ARTICLE_PUBLISHED->value)->toBe('article_published');
        expect(NotificationType::NEW_COMMENT->value)->toBe('new_comment');
        expect(NotificationType::NEWSLETTER->value)->toBe('newsletter');
        expect(NotificationType::SYSTEM_ALERT->value)->toBe('system_alert');
    });

    it('can be created from string value', function () {
        // Act & Assert
        expect(NotificationType::from('article_published'))->toBe(NotificationType::ARTICLE_PUBLISHED);
        expect(NotificationType::from('new_comment'))->toBe(NotificationType::NEW_COMMENT);
        expect(NotificationType::from('newsletter'))->toBe(NotificationType::NEWSLETTER);
        expect(NotificationType::from('system_alert'))->toBe(NotificationType::SYSTEM_ALERT);
    });

    it('can be created from string value using tryFrom', function () {
        // Act & Assert
        expect(NotificationType::tryFrom('article_published'))->toBe(NotificationType::ARTICLE_PUBLISHED);
        expect(NotificationType::tryFrom('new_comment'))->toBe(NotificationType::NEW_COMMENT);
        expect(NotificationType::tryFrom('newsletter'))->toBe(NotificationType::NEWSLETTER);
        expect(NotificationType::tryFrom('system_alert'))->toBe(NotificationType::SYSTEM_ALERT);
        expect(NotificationType::tryFrom('invalid'))->toBeNull();
    });

    it('can be serialized to JSON', function () {
        // Act
        $json = json_encode(NotificationType::ARTICLE_PUBLISHED);

        // Assert
        expect($json)->toBe('"article_published"');
    });

    it('can be used in match expressions', function () {
        // Act
        $result = match (NotificationType::NEW_COMMENT) {
            NotificationType::ARTICLE_PUBLISHED => 'article',
            NotificationType::NEW_COMMENT => 'comment',
            NotificationType::NEWSLETTER => 'newsletter',
            NotificationType::SYSTEM_ALERT => 'alert',
        };

        // Assert
        expect($result)->toBe('comment');
    });
});
