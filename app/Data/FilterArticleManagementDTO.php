<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ArticleStatus;
use App\Http\Requests\V1\Admin\Article\GetArticlesRequest;

/**
 * Data Transfer Object for filtering articles in admin management
 */
final class FilterArticleManagementDTO
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 15,
        public readonly string $sortBy = 'created_at',
        public readonly string $sortDirection = 'desc',
        public readonly ?string $search = null,
        public readonly ?ArticleStatus $status = null,
        public readonly ?int $authorId = null,
        public readonly ?int $categoryId = null,
        public readonly ?int $tagId = null,
        public readonly ?bool $isFeatured = null,
        public readonly ?bool $isPinned = null,
        public readonly ?bool $hasReports = null,
        public readonly ?string $createdAfter = null,
        public readonly ?string $createdBefore = null,
        public readonly ?string $publishedAfter = null,
        public readonly ?string $publishedBefore = null,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(GetArticlesRequest $request): self
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
            sortBy: isset($params['sort_by']) ? (string) $params['sort_by'] : 'created_at',
            sortDirection: isset($params['sort_direction']) ? (string) $params['sort_direction'] : 'desc',
            search: isset($params['search']) ? (string) $params['search'] : null,
            status: isset($params['status']) ? ArticleStatus::from((string) $params['status']) : null,
            authorId: isset($params['author_id']) ? (int) $params['author_id'] : null,
            categoryId: isset($params['category_id']) ? (int) $params['category_id'] : null,
            tagId: isset($params['tag_id']) ? (int) $params['tag_id'] : null,
            isFeatured: isset($params['is_featured']) ? (bool) $params['is_featured'] : null,
            isPinned: isset($params['is_pinned']) ? (bool) $params['is_pinned'] : null,
            hasReports: isset($params['has_reports']) ? (bool) $params['has_reports'] : null,
            createdAfter: isset($params['created_after']) ? (string) $params['created_after'] : null,
            createdBefore: isset($params['created_before']) ? (string) $params['created_before'] : null,
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

        if ($this->authorId !== null) {
            $data['author_id'] = $this->authorId;
        }

        if ($this->categoryId !== null) {
            $data['category_id'] = $this->categoryId;
        }

        if ($this->tagId !== null) {
            $data['tag_id'] = $this->tagId;
        }

        if ($this->isFeatured !== null) {
            $data['is_featured'] = $this->isFeatured;
        }

        if ($this->isPinned !== null) {
            $data['is_pinned'] = $this->isPinned;
        }

        if ($this->hasReports !== null) {
            $data['has_reports'] = $this->hasReports;
        }

        if ($this->createdAfter !== null) {
            $data['created_after'] = $this->createdAfter;
        }

        if ($this->createdBefore !== null) {
            $data['created_before'] = $this->createdBefore;
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
