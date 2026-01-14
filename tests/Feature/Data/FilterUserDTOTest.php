<?php

declare(strict_types=1);

use App\Data\FilterUserDTO;

describe('FilterUserDTO', function () {
    it('can be created with default values', function () {
        // Act
        $dto = new FilterUserDTO;

        // Assert
        expect($dto->page)->toBe(1);
        expect($dto->perPage)->toBe(15);
        expect($dto->search)->toBeNull();
        expect($dto->roleId)->toBeNull();
        expect($dto->status)->toBeNull();
        expect($dto->createdAfter)->toBeNull();
        expect($dto->createdBefore)->toBeNull();
        expect($dto->sortBy)->toBe('created_at');
        expect($dto->sortDirection)->toBe('desc');
    });

    it('can be created with custom values', function () {
        // Act
        $dto = new FilterUserDTO(
            page: 2,
            perPage: 20,
            search: 'john',
            roleId: 1,
            status: 'active',
            createdAfter: '2024-01-01',
            createdBefore: '2024-12-31',
            sortBy: 'name',
            sortDirection: 'asc'
        );

        // Assert
        expect($dto->page)->toBe(2);
        expect($dto->perPage)->toBe(20);
        expect($dto->search)->toBe('john');
        expect($dto->roleId)->toBe(1);
        expect($dto->status)->toBe('active');
        expect($dto->createdAfter)->toBe('2024-01-01');
        expect($dto->createdBefore)->toBe('2024-12-31');
        expect($dto->sortBy)->toBe('name');
        expect($dto->sortDirection)->toBe('asc');
    });

    it('converts to array correctly', function () {
        // Arrange
        $dto = new FilterUserDTO(
            page: 2,
            perPage: 20,
            search: 'test',
            roleId: 1,
            status: 'active',
            createdAfter: '2024-01-01',
            createdBefore: '2024-12-31',
            sortBy: 'name',
            sortDirection: 'asc'
        );

        // Act
        $array = $dto->toArray();

        // Assert
        expect($array)->toBe([
            'page' => 2,
            'per_page' => 20,
            'search' => 'test',
            'role_id' => 1,
            'status' => 'active',
            'created_after' => '2024-01-01',
            'created_before' => '2024-12-31',
            'sort_by' => 'name',
            'sort_direction' => 'asc',
        ]);
    });
});
