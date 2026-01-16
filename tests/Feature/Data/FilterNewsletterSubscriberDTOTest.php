<?php

declare(strict_types=1);

use App\Data\Newsletter\FilterNewsletterSubscriberDTO;

describe('FilterNewsletterSubscriberDTO', function () {
    it('can be created with default values', function () {
        // Act
        $dto = new FilterNewsletterSubscriberDTO;

        // Assert
        expect($dto->search)->toBeNull();
        expect($dto->status)->toBeNull();
        expect($dto->subscribedAtFrom)->toBeNull();
        expect($dto->subscribedAtTo)->toBeNull();
        expect($dto->sortBy)->toBe('created_at');
        expect($dto->sortOrder)->toBe('desc');
        expect($dto->page)->toBe(1);
        expect($dto->perPage)->toBe(15);
    });

    it('can be created with custom values', function () {
        // Act
        $dto = new FilterNewsletterSubscriberDTO(
            search: 'test@example.com',
            status: 'verified',
            subscribedAtFrom: '2024-01-01',
            subscribedAtTo: '2024-12-31',
            sortBy: 'email',
            sortOrder: 'asc',
            page: 2,
            perPage: 20
        );

        // Assert
        expect($dto->search)->toBe('test@example.com');
        expect($dto->status)->toBe('verified');
        expect($dto->subscribedAtFrom)->toBe('2024-01-01');
        expect($dto->subscribedAtTo)->toBe('2024-12-31');
        expect($dto->sortBy)->toBe('email');
        expect($dto->sortOrder)->toBe('asc');
        expect($dto->page)->toBe(2);
        expect($dto->perPage)->toBe(20);
    });

    it('converts to array correctly with all fields', function () {
        // Arrange
        $dto = new FilterNewsletterSubscriberDTO(
            search: 'test',
            status: 'verified',
            subscribedAtFrom: '2024-01-01',
            subscribedAtTo: '2024-12-31',
            sortBy: 'email',
            sortOrder: 'asc',
            page: 2,
            perPage: 20
        );

        // Act
        $array = $dto->toArray();

        // Assert
        expect($array)->toBe([
            'search' => 'test',
            'status' => 'verified',
            'subscribed_at_from' => '2024-01-01',
            'subscribed_at_to' => '2024-12-31',
            'sort_by' => 'email',
            'sort_order' => 'asc',
            'page' => 2,
            'per_page' => 20,
        ]);
    });

    it('converts to array with only set fields', function () {
        // Arrange
        $dto = new FilterNewsletterSubscriberDTO(
            search: 'test'
        );

        // Act
        $array = $dto->toArray();

        // Assert
        expect($array['search'])->toBe('test');
        expect($array)->not->toHaveKey('status');
        expect($array)->not->toHaveKey('subscribed_at_from');
    });
});
