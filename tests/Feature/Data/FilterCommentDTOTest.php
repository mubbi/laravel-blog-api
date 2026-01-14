<?php

declare(strict_types=1);

use App\Data\FilterCommentDTO;
use App\Enums\CommentStatus;

describe('FilterCommentDTO', function () {
    it('can be created with default values', function () {
        // Act
        $dto = new FilterCommentDTO;

        // Assert
        expect($dto->search)->toBeNull();
        expect($dto->status)->toBeNull();
        expect($dto->userId)->toBeNull();
        expect($dto->articleId)->toBeNull();
        expect($dto->parentCommentId)->toBeNull();
        expect($dto->approvedBy)->toBeNull();
        expect($dto->hasReports)->toBeNull();
        expect($dto->sortBy)->toBe('created_at');
        expect($dto->sortOrder)->toBe('desc');
        expect($dto->perPage)->toBe(15);
    });

    it('can be created with custom values', function () {
        // Act
        $dto = new FilterCommentDTO(
            search: 'test',
            status: CommentStatus::APPROVED,
            userId: 1,
            articleId: 2,
            parentCommentId: 3,
            approvedBy: 4,
            hasReports: true,
            sortBy: 'updated_at',
            sortOrder: 'asc',
            perPage: 20
        );

        // Assert
        expect($dto->search)->toBe('test');
        expect($dto->status)->toBe(CommentStatus::APPROVED);
        expect($dto->userId)->toBe(1);
        expect($dto->articleId)->toBe(2);
        expect($dto->parentCommentId)->toBe(3);
        expect($dto->approvedBy)->toBe(4);
        expect($dto->hasReports)->toBeTrue();
        expect($dto->sortBy)->toBe('updated_at');
        expect($dto->sortOrder)->toBe('asc');
        expect($dto->perPage)->toBe(20);
    });

    it('converts to array correctly', function () {
        // Arrange
        $dto = new FilterCommentDTO(
            search: 'test',
            status: CommentStatus::PENDING,
            userId: 1,
            articleId: 2,
            hasReports: true
        );

        // Act
        $array = $dto->toArray();

        // Assert
        expect($array)->toHaveKeys([
            'search', 'user_id', 'article_id', 'parent_comment_id',
            'approved_by', 'status', 'has_reports', 'sort_by', 'sort_order', 'per_page',
        ]);
        expect($array['status'])->toBe(CommentStatus::PENDING->value);
        expect($array['has_reports'])->toBeTrue();
    });

    it('converts to array with null values handled correctly', function () {
        // Arrange
        $dto = new FilterCommentDTO;

        // Act
        $array = $dto->toArray();

        // Assert
        expect($array['search'])->toBeNull();
        expect($array['user_id'])->toBeNull();
        expect($array)->not->toHaveKey('status');
        expect($array)->not->toHaveKey('has_reports');
    });
});
