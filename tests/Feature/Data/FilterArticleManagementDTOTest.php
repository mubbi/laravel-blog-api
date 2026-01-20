<?php

declare(strict_types=1);

use App\Data\Article\FilterArticleManagementDTO;
use App\Enums\ArticleStatus;

describe('FilterArticleManagementDTO', function () {
    it('can be created with default values', function () {
        // Act
        $dto = new FilterArticleManagementDTO;

        // Assert
        expect($dto->page)->toBe(1);
        expect($dto->perPage)->toBe(15);
        expect($dto->sortBy)->toBe('created_at');
        expect($dto->sortDirection)->toBe('desc');
        expect($dto->search)->toBeNull();
        expect($dto->status)->toBeNull();
        expect($dto->authorId)->toBeNull();
        expect($dto->categoryId)->toBeNull();
        expect($dto->tagId)->toBeNull();
        expect($dto->isFeatured)->toBeNull();
        expect($dto->isPinned)->toBeNull();
        expect($dto->hasReports)->toBeNull();
    });

    it('can be created with custom values', function () {
        // Act
        $dto = new FilterArticleManagementDTO(
            page: 2,
            perPage: 20,
            sortBy: 'title',
            sortDirection: 'asc',
            search: 'test',
            status: ArticleStatus::PUBLISHED,
            authorId: 1,
            categoryId: 2,
            tagId: 3,
            isFeatured: true,
            isPinned: false,
            hasReports: true,
            createdAfter: '2024-01-01',
            createdBefore: '2024-12-31',
            publishedAfter: '2024-06-01',
            publishedBefore: '2024-06-30'
        );

        // Assert
        expect($dto->page)->toBe(2);
        expect($dto->perPage)->toBe(20);
        expect($dto->sortBy)->toBe('title');
        expect($dto->sortDirection)->toBe('asc');
        expect($dto->search)->toBe('test');
        expect($dto->status)->toBe(ArticleStatus::PUBLISHED);
        expect($dto->authorId)->toBe(1);
        expect($dto->categoryId)->toBe(2);
        expect($dto->tagId)->toBe(3);
        expect($dto->isFeatured)->toBeTrue();
        expect($dto->isPinned)->toBeFalse();
        expect($dto->hasReports)->toBeTrue();
        expect($dto->createdAfter)->toBe('2024-01-01');
        expect($dto->createdBefore)->toBe('2024-12-31');
        expect($dto->publishedAfter)->toBe('2024-06-01');
        expect($dto->publishedBefore)->toBe('2024-06-30');
    });

    it('can be created from array with all fields', function () {
        // Arrange
        $params = [
            'page' => 3,
            'per_page' => 25,
            'sort_by' => 'published_at',
            'sort_direction' => 'asc',
            'search' => 'laravel',
            'status' => ArticleStatus::DRAFT->value,
            'author_id' => 5,
            'category_id' => 6,
            'tag_id' => 7,
            'is_featured' => true,
            'is_pinned' => false,
            'has_reports' => true,
            'created_after' => '2024-01-01',
            'created_before' => '2024-12-31',
            'published_after' => '2024-06-01',
            'published_before' => '2024-06-30',
        ];

        // Act
        $dto = FilterArticleManagementDTO::fromArray($params);

        // Assert
        expect($dto->page)->toBe(3);
        expect($dto->perPage)->toBe(25);
        expect($dto->sortBy)->toBe('published_at');
        expect($dto->sortDirection)->toBe('asc');
        expect($dto->search)->toBe('laravel');
        expect($dto->status)->toBe(ArticleStatus::DRAFT);
        expect($dto->authorId)->toBe(5);
        expect($dto->categoryId)->toBe(6);
        expect($dto->tagId)->toBe(7);
        expect($dto->isFeatured)->toBeTrue();
        expect($dto->isPinned)->toBeFalse();
        expect($dto->hasReports)->toBeTrue();
    });

    it('converts to array correctly with all fields', function () {
        // Arrange
        $dto = new FilterArticleManagementDTO(
            page: 2,
            perPage: 20,
            sortBy: 'title',
            sortDirection: 'asc',
            search: 'test',
            status: ArticleStatus::PUBLISHED,
            authorId: 1,
            categoryId: 2,
            tagId: 3,
            isFeatured: true,
            isPinned: false,
            hasReports: true,
            createdAfter: '2024-01-01',
            createdBefore: '2024-12-31',
            publishedAfter: '2024-06-01',
            publishedBefore: '2024-06-30'
        );

        // Act
        $array = $dto->toArray();

        // Assert
        expect($array)->toHaveKeys([
            'page', 'per_page', 'sort_by', 'sort_direction',
            'search', 'status', 'author_id', 'category_id', 'tag_id',
            'is_featured', 'is_pinned', 'has_reports',
            'created_after', 'created_before',
            'published_after', 'published_before',
        ]);
        expect($array['status'])->toBe(ArticleStatus::PUBLISHED->value);
    });

    it('converts to array with only set fields', function () {
        // Arrange
        $dto = new FilterArticleManagementDTO(
            page: 1,
            perPage: 15,
            search: 'test'
        );

        // Act
        $array = $dto->toArray();

        // Assert
        expect($array)->toHaveKeys(['page', 'per_page', 'sort_by', 'sort_direction', 'search']);
        expect($array)->not->toHaveKey('status');
        expect($array)->not->toHaveKey('author_id');
    });
});
