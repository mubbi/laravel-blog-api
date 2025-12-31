<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ArticleStatus;
use App\Http\Requests\V1\Article\GetArticlesRequest;

/**
 * Data Transfer Object for filtering articles
 */
final class FilterArticleDTO
{
    /**
     * @param  array<string>|null  $categorySlugs
     * @param  array<string>|null  $tagSlugs
     */
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 15,
        public readonly string $sortBy = 'published_at',
        public readonly string $sortDirection = 'desc',
        public readonly ?string $search = null,
        public readonly ?ArticleStatus $status = null,
        public readonly ?array $categorySlugs = null,
        public readonly ?array $tagSlugs = null,
        public readonly ?int $authorId = null,
        public readonly ?int $createdBy = null,
        public readonly ?string $publishedAfter = null,
        public readonly ?string $publishedBefore = null,
    ) {}

    /**
     * Create DTO from public article request
     */
    public static function fromPublicRequest(GetArticlesRequest $request): self
    {
        return self::fromArray($request->validated());
    }

    /**
     * Create DTO from array (for backward compatibility)
     *
     * @param  array<string, mixed>  $params
     */
    public static function fromArray(array $params): self
    {
        return new self(
            page: isset($params['page']) ? (int) $params['page'] : 1,
            perPage: isset($params['per_page']) ? (int) $params['per_page'] : 15,
            sortBy: isset($params['sort_by']) ? (string) $params['sort_by'] : 'published_at',
            sortDirection: isset($params['sort_direction']) ? (string) $params['sort_direction'] : 'desc',
            search: isset($params['search']) ? (string) $params['search'] : null,
            status: isset($params['status']) ? ArticleStatus::from((string) $params['status']) : null,
            categorySlugs: isset($params['category_slug']) ? (is_array($params['category_slug']) ? array_map(fn ($v) => (string) $v, $params['category_slug']) : [(string) $params['category_slug']]) : null,
            tagSlugs: isset($params['tag_slug']) ? (is_array($params['tag_slug']) ? array_map(fn ($v) => (string) $v, $params['tag_slug']) : [(string) $params['tag_slug']]) : null,
            authorId: isset($params['author_id']) ? (int) $params['author_id'] : null,
            createdBy: isset($params['created_by']) ? (int) $params['created_by'] : null,
            publishedAfter: isset($params['published_after']) ? (string) $params['published_after'] : null,
            publishedBefore: isset($params['published_before']) ? (string) $params['published_before'] : null,
        );
    }

    /**
     * Convert to array for backward compatibility
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'page' => $this->page,
            'per_page' => $this->perPage,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
        ];

        if ($this->search !== null) {
            $data['search'] = $this->search;
        }

        if ($this->status !== null) {
            $data['status'] = $this->status->value;
        }

        if ($this->categorySlugs !== null) {
            $data['category_slug'] = $this->categorySlugs;
        }

        if ($this->tagSlugs !== null) {
            $data['tag_slug'] = $this->tagSlugs;
        }

        if ($this->authorId !== null) {
            $data['author_id'] = $this->authorId;
        }

        if ($this->createdBy !== null) {
            $data['created_by'] = $this->createdBy;
        }

        if ($this->publishedAfter !== null) {
            $data['published_after'] = $this->publishedAfter;
        }

        if ($this->publishedBefore !== null) {
            $data['published_before'] = $this->publishedBefore;
        }

        return $data;
    }
}
