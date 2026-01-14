<?php

declare(strict_types=1);

use App\Data\FilterArticleDTO;
use App\Enums\ArticleStatus;

describe('FilterArticleDTO', function () {
    it('can be created with default values', function () {
        // Act
        $dto = new FilterArticleDTO;

        // Assert
        expect($dto->page)->toBe(1);
        expect($dto->perPage)->toBe(15);
        expect($dto->sortBy)->toBe('published_at');
        expect($dto->sortDirection)->toBe('desc');
        expect($dto->search)->toBeNull();
        expect($dto->status)->toBeNull();
        expect($dto->categorySlugs)->toBeNull();
        expect($dto->tagSlugs)->toBeNull();
        expect($dto->authorId)->toBeNull();
        expect($dto->createdBy)->toBeNull();
        expect($dto->publishedAfter)->toBeNull();
        expect($dto->publishedBefore)->toBeNull();
    });

    it('can be created with custom values', function () {
        // Act
        $dto = new FilterArticleDTO(
            page: 2,
            perPage: 20,
            sortBy: 'title',
            sortDirection: 'asc',
            search: 'test',
            status: ArticleStatus::PUBLISHED,
            categorySlugs: ['tech', 'programming'],
            tagSlugs: ['php', 'laravel'],
            authorId: 1,
            createdBy: 2,
            publishedAfter: '2024-01-01',
            publishedBefore: '2024-12-31'
        );

        // Assert
        expect($dto->page)->toBe(2);
        expect($dto->perPage)->toBe(20);
        expect($dto->sortBy)->toBe('title');
        expect($dto->sortDirection)->toBe('asc');
        expect($dto->search)->toBe('test');
        expect($dto->status)->toBe(ArticleStatus::PUBLISHED);
        expect($dto->categorySlugs)->toBe(['tech', 'programming']);
        expect($dto->tagSlugs)->toBe(['php', 'laravel']);
        expect($dto->authorId)->toBe(1);
        expect($dto->createdBy)->toBe(2);
        expect($dto->publishedAfter)->toBe('2024-01-01');
        expect($dto->publishedBefore)->toBe('2024-12-31');
    });

    it('can be created from array with all fields', function () {
        // Arrange
        $params = [
            'page' => 3,
            'per_page' => 25,
            'sort_by' => 'created_at',
            'sort_direction' => 'asc',
            'search' => 'laravel',
            'status' => ArticleStatus::DRAFT->value,
            'category_slug' => ['tech'],
            'tag_slug' => ['php'],
            'author_id' => 5,
            'created_by' => 6,
            'published_after' => '2024-06-01',
            'published_before' => '2024-06-30',
        ];

        // Act
        $dto = FilterArticleDTO::fromArray($params);

        // Assert
        expect($dto->page)->toBe(3);
        expect($dto->perPage)->toBe(25);
        expect($dto->sortBy)->toBe('created_at');
        expect($dto->sortDirection)->toBe('asc');
        expect($dto->search)->toBe('laravel');
        expect($dto->status)->toBe(ArticleStatus::DRAFT);
        expect($dto->categorySlugs)->toBe(['tech']);
        expect($dto->tagSlugs)->toBe(['php']);
        expect($dto->authorId)->toBe(5);
        expect($dto->createdBy)->toBe(6);
        expect($dto->publishedAfter)->toBe('2024-06-01');
        expect($dto->publishedBefore)->toBe('2024-06-30');
    });

    it('can be created from array with partial fields', function () {
        // Arrange
        $params = [
            'page' => 2,
            'search' => 'test',
        ];

        // Act
        $dto = FilterArticleDTO::fromArray($params);

        // Assert
        expect($dto->page)->toBe(2);
        expect($dto->perPage)->toBe(15); // Default
        expect($dto->sortBy)->toBe('published_at'); // Default
        expect($dto->sortDirection)->toBe('desc'); // Default
        expect($dto->search)->toBe('test');
        expect($dto->status)->toBeNull();
    });

    it('handles category_slug as single string', function () {
        // Arrange
        $params = [
            'category_slug' => 'tech',
        ];

        // Act
        $dto = FilterArticleDTO::fromArray($params);

        // Assert
        expect($dto->categorySlugs)->toBe(['tech']);
    });

    it('handles tag_slug as single string', function () {
        // Arrange
        $params = [
            'tag_slug' => 'php',
        ];

        // Act
        $dto = FilterArticleDTO::fromArray($params);

        // Assert
        expect($dto->tagSlugs)->toBe(['php']);
    });

    it('converts to array correctly', function () {
        // Arrange
        $dto = new FilterArticleDTO(
            page: 2,
            perPage: 20,
            sortBy: 'title',
            sortDirection: 'asc',
            search: 'test',
            status: ArticleStatus::PUBLISHED,
            categorySlugs: ['tech'],
            tagSlugs: ['php'],
            authorId: 1,
            createdBy: 2,
            publishedAfter: '2024-01-01',
            publishedBefore: '2024-12-31'
        );

        // Act
        $array = $dto->toArray();

        // Assert
        expect($array)->toBe([
            'page' => 2,
            'per_page' => 20,
            'sort_by' => 'title',
            'sort_direction' => 'asc',
            'search' => 'test',
            'status' => ArticleStatus::PUBLISHED->value,
            'category_slug' => ['tech'],
            'tag_slug' => ['php'],
            'author_id' => 1,
            'created_by' => 2,
            'published_after' => '2024-01-01',
            'published_before' => '2024-12-31',
        ]);
    });

    it('converts to array with only set fields', function () {
        // Arrange
        $dto = new FilterArticleDTO(
            page: 1,
            perPage: 15,
            search: 'test'
        );

        // Act
        $array = $dto->toArray();

        // Assert
        expect($array)->toHaveKeys(['page', 'per_page', 'sort_by', 'sort_direction', 'search']);
        expect($array)->not->toHaveKey('status');
        expect($array)->not->toHaveKey('category_slug');
        expect($array)->not->toHaveKey('tag_slug');
    });

    it('handles empty array input', function () {
        // Act
        $dto = FilterArticleDTO::fromArray([]);

        // Assert
        expect($dto->page)->toBe(1);
        expect($dto->perPage)->toBe(15);
        expect($dto->sortBy)->toBe('published_at');
        expect($dto->sortDirection)->toBe('desc');
        expect($dto->search)->toBeNull();
    });
});
