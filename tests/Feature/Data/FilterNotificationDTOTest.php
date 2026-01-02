<?php

declare(strict_types=1);

use App\Data\FilterNotificationDTO;
use App\Enums\NotificationType;

describe('FilterNotificationDTO', function () {
    it('can be created with default values', function () {
        // Act
        $dto = new FilterNotificationDTO;

        // Assert
        expect($dto->search)->toBeNull();
        expect($dto->type)->toBeNull();
        expect($dto->createdAtFrom)->toBeNull();
        expect($dto->createdAtTo)->toBeNull();
        expect($dto->sortBy)->toBe('created_at');
        expect($dto->sortOrder)->toBe('desc');
        expect($dto->perPage)->toBe(15);
    });

    it('can be created with custom values', function () {
        // Act
        $dto = new FilterNotificationDTO(
            search: 'test',
            type: NotificationType::ARTICLE_PUBLISHED,
            createdAtFrom: '2024-01-01',
            createdAtTo: '2024-12-31',
            sortBy: 'updated_at',
            sortOrder: 'asc',
            perPage: 20
        );

        // Assert
        expect($dto->search)->toBe('test');
        expect($dto->type)->toBe(NotificationType::ARTICLE_PUBLISHED);
        expect($dto->createdAtFrom)->toBe('2024-01-01');
        expect($dto->createdAtTo)->toBe('2024-12-31');
        expect($dto->sortBy)->toBe('updated_at');
        expect($dto->sortOrder)->toBe('asc');
        expect($dto->perPage)->toBe(20);
    });

    it('converts to array correctly with type', function () {
        // Arrange
        $dto = new FilterNotificationDTO(
            search: 'test',
            type: NotificationType::NEW_COMMENT,
            createdAtFrom: '2024-01-01',
            createdAtTo: '2024-12-31'
        );

        // Act
        $array = $dto->toArray();

        // Assert
        expect($array)->toHaveKeys([
            'search', 'type', 'created_at_from', 'created_at_to',
            'sort_by', 'sort_order', 'per_page',
        ]);
        expect($array['type'])->toBe(NotificationType::NEW_COMMENT->value);
    });

    it('converts to array without type when null', function () {
        // Arrange
        $dto = new FilterNotificationDTO(
            search: 'test',
            type: null
        );

        // Act
        $array = $dto->toArray();

        // Assert
        expect($array)->not->toHaveKey('type');
    });
});
