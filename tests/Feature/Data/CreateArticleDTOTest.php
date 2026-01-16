<?php

declare(strict_types=1);

use App\Data\CreateArticleDTO;
use App\Enums\ArticleStatus;

describe('CreateArticleDTO', function () {
    it('can be created with all properties', function () {
        // Arrange
        $publishedAt = now();
        $categoryIds = [1, 2, 3];
        $tagIds = [4, 5];
        $authors = [
            ['user_id' => 1, 'role' => 'main'],
            ['user_id' => 2, 'role' => 'co_author'],
        ];

        // Act
        $dto = new CreateArticleDTO(
            slug: 'test-article',
            title: 'Test Article',
            subtitle: 'Test Subtitle',
            excerpt: 'Test excerpt',
            contentMarkdown: '# Content',
            contentHtml: '<h1>Content</h1>',
            featuredMediaId: 1,
            status: ArticleStatus::PUBLISHED,
            publishedAt: $publishedAt,
            metaTitle: 'Meta Title',
            metaDescription: 'Meta Description',
            createdBy: 1,
            approvedBy: 2,
            categoryIds: $categoryIds,
            tagIds: $tagIds,
            authors: $authors
        );

        // Assert
        expect($dto->slug)->toBe('test-article');
        expect($dto->title)->toBe('Test Article');
        expect($dto->subtitle)->toBe('Test Subtitle');
        expect($dto->excerpt)->toBe('Test excerpt');
        expect($dto->contentMarkdown)->toBe('# Content');
        expect($dto->contentHtml)->toBe('<h1>Content</h1>');
        expect($dto->featuredMediaId)->toBe(1);
        expect($dto->status)->toBe(ArticleStatus::PUBLISHED);
        expect($dto->publishedAt)->toBe($publishedAt);
        expect($dto->metaTitle)->toBe('Meta Title');
        expect($dto->metaDescription)->toBe('Meta Description');
        expect($dto->createdBy)->toBe(1);
        expect($dto->approvedBy)->toBe(2);
        expect($dto->categoryIds)->toBe($categoryIds);
        expect($dto->tagIds)->toBe($tagIds);
        expect($dto->authors)->toBe($authors);
    });

    it('can be created with nullable properties', function () {
        // Act
        $dto = new CreateArticleDTO(
            slug: 'test-article',
            title: 'Test Article',
            subtitle: null,
            excerpt: null,
            contentMarkdown: '# Content',
            contentHtml: null,
            featuredMediaId: null,
            status: ArticleStatus::DRAFT,
            publishedAt: null,
            metaTitle: null,
            metaDescription: null,
            createdBy: 1,
            approvedBy: null,
            categoryIds: [],
            tagIds: [],
            authors: []
        );

        // Assert
        expect($dto->subtitle)->toBeNull();
        expect($dto->excerpt)->toBeNull();
        expect($dto->contentHtml)->toBeNull();
        expect($dto->featuredMediaId)->toBeNull();
        expect($dto->publishedAt)->toBeNull();
        expect($dto->metaTitle)->toBeNull();
        expect($dto->metaDescription)->toBeNull();
        expect($dto->approvedBy)->toBeNull();
        expect($dto->categoryIds)->toBe([]);
        expect($dto->tagIds)->toBe([]);
        expect($dto->authors)->toBe([]);
    });

    it('converts to array correctly with all fields', function () {
        // Arrange
        $publishedAt = now();
        $dto = new CreateArticleDTO(
            slug: 'test-article',
            title: 'Test Article',
            subtitle: 'Test Subtitle',
            excerpt: 'Test excerpt',
            contentMarkdown: '# Content',
            contentHtml: '<h1>Content</h1>',
            featuredMediaId: 1,
            status: ArticleStatus::PUBLISHED,
            publishedAt: $publishedAt,
            metaTitle: 'Meta Title',
            metaDescription: 'Meta Description',
            createdBy: 1,
            approvedBy: 2,
            categoryIds: [1, 2],
            tagIds: [3, 4],
            authors: [['user_id' => 1, 'role' => 'main']]
        );

        // Act
        $array = $dto->toArray();

        // Assert
        expect($array)->toBe([
            'slug' => 'test-article',
            'title' => 'Test Article',
            'subtitle' => 'Test Subtitle',
            'excerpt' => 'Test excerpt',
            'content_markdown' => '# Content',
            'content_html' => '<h1>Content</h1>',
            'featured_media_id' => 1,
            'status' => ArticleStatus::PUBLISHED->value,
            'published_at' => $publishedAt,
            'meta_title' => 'Meta Title',
            'meta_description' => 'Meta Description',
            'created_by' => 1,
            'approved_by' => 2,
        ]);
    });

    it('converts to array correctly with null values', function () {
        // Arrange
        $dto = new CreateArticleDTO(
            slug: 'test-article',
            title: 'Test Article',
            subtitle: null,
            excerpt: null,
            contentMarkdown: '# Content',
            contentHtml: null,
            featuredMediaId: null,
            status: ArticleStatus::DRAFT,
            publishedAt: null,
            metaTitle: null,
            metaDescription: null,
            createdBy: 1,
            approvedBy: null,
            categoryIds: [],
            tagIds: [],
            authors: []
        );

        // Act
        $array = $dto->toArray();

        // Assert
        expect($array['subtitle'])->toBeNull();
        expect($array['excerpt'])->toBeNull();
        expect($array['content_html'])->toBeNull();
        expect($array['featured_media_id'])->toBeNull();
        expect($array['published_at'])->toBeNull();
        expect($array['meta_title'])->toBeNull();
        expect($array['meta_description'])->toBeNull();
        expect($array['approved_by'])->toBeNull();
    });

    it('converts status enum to string value in array', function () {
        // Arrange
        $dto = new CreateArticleDTO(
            slug: 'test-article',
            title: 'Test Article',
            subtitle: null,
            excerpt: null,
            contentMarkdown: '# Content',
            contentHtml: null,
            featuredMediaId: null,
            status: ArticleStatus::SCHEDULED,
            publishedAt: null,
            metaTitle: null,
            metaDescription: null,
            createdBy: 1,
            approvedBy: null,
            categoryIds: [],
            tagIds: [],
            authors: []
        );

        // Act
        $array = $dto->toArray();

        // Assert
        expect($array['status'])->toBe('scheduled');
    });
});
