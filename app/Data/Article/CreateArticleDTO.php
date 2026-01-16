<?php

declare(strict_types=1);

namespace App\Data\Article;

use App\Enums\ArticleStatus;
use App\Http\Requests\V1\Article\CreateArticleRequest;
use Carbon\Carbon;

/**
 * Data Transfer Object for creating an article
 */
final class CreateArticleDTO
{
    /**
     * @param  array<int>  $categoryIds
     * @param  array<int>  $tagIds
     * @param  array<array{user_id: int, role: string}>  $authors
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly ?string $subtitle,
        public readonly ?string $excerpt,
        public readonly string $contentMarkdown,
        public readonly ?string $contentHtml,
        public readonly ?int $featuredMediaId,
        public readonly ArticleStatus $status,
        public readonly ?\DateTimeInterface $publishedAt,
        public readonly ?string $metaTitle,
        public readonly ?string $metaDescription,
        public readonly int $createdBy,
        public readonly ?int $approvedBy,
        public readonly array $categoryIds,
        public readonly array $tagIds,
        public readonly array $authors,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(CreateArticleRequest $request): self
    {
        $validated = $request->validated();
        $user = $request->user();
        assert($user !== null);

        // Determine status based on published_at
        $publishedAt = null;
        if (isset($validated['published_at']) && is_string($validated['published_at'])) {
            $publishedAt = Carbon::parse($validated['published_at']);
        }
        $status = ArticleStatus::DRAFT;

        if ($publishedAt !== null) {
            // If published_at is in the future, it's scheduled
            if ($publishedAt->isFuture()) {
                $status = ArticleStatus::SCHEDULED;
            } else {
                // If published_at is now or in the past, it's published
                $status = ArticleStatus::PUBLISHED;
            }
        }

        // Set approved_by if status is published or scheduled
        $approvedBy = ($status === ArticleStatus::PUBLISHED || $status === ArticleStatus::SCHEDULED) ? $user->id : null;

        // Process authors array
        $authors = [];
        if (isset($validated['authors']) && is_array($validated['authors'])) {
            /** @var array<int, array<string, mixed>> $validatedAuthors */
            $validatedAuthors = $validated['authors'];
            foreach ($validatedAuthors as $author) {
                assert(is_array($author));
                $authors[] = [
                    'user_id' => (int) $author['user_id'],
                    'role' => (string) ($author['role'] ?? 'main'),
                ];
            }
        } else {
            // Default to creator as main author
            $authors[] = [
                'user_id' => $user->id,
                'role' => 'main',
            ];
        }

        return new self(
            slug: (string) $validated['slug'],
            title: (string) $validated['title'],
            subtitle: isset($validated['subtitle']) ? (string) $validated['subtitle'] : null,
            excerpt: isset($validated['excerpt']) ? (string) $validated['excerpt'] : null,
            contentMarkdown: (string) $validated['content_markdown'],
            contentHtml: isset($validated['content_html']) ? (string) $validated['content_html'] : null,
            featuredMediaId: isset($validated['featured_media_id']) ? (int) $validated['featured_media_id'] : null,
            status: $status,
            publishedAt: $publishedAt,
            metaTitle: isset($validated['meta_title']) ? (string) $validated['meta_title'] : null,
            metaDescription: isset($validated['meta_description']) ? (string) $validated['meta_description'] : null,
            createdBy: $user->id,
            approvedBy: $approvedBy,
            categoryIds: isset($validated['category_ids']) && is_array($validated['category_ids'])
                ? array_map(static fn (mixed $value): int => (int) $value, $validated['category_ids'])
                : [],
            tagIds: isset($validated['tag_ids']) && is_array($validated['tag_ids'])
                ? array_map(static fn (mixed $value): int => (int) $value, $validated['tag_ids'])
                : [],
            authors: $authors,
        );
    }

    /**
     * Convert to array for database operations
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'excerpt' => $this->excerpt,
            'content_markdown' => $this->contentMarkdown,
            'content_html' => $this->contentHtml,
            'featured_media_id' => $this->featuredMediaId,
            'status' => $this->status->value,
            'published_at' => $this->publishedAt,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'created_by' => $this->createdBy,
            'approved_by' => $this->approvedBy,
        ];
    }
}
