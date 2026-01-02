<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\CommentStatus;
use App\Http\Requests\V1\Admin\Comment\GetCommentsRequest;

/**
 * Data Transfer Object for filtering comments
 */
final class FilterCommentDTO
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?CommentStatus $status = null,
        public readonly ?int $userId = null,
        public readonly ?int $articleId = null,
        public readonly ?int $parentCommentId = null,
        public readonly ?int $approvedBy = null,
        public readonly ?bool $hasReports = null,
        public readonly string $sortBy = 'created_at',
        public readonly string $sortOrder = 'desc',
        public readonly int $perPage = 15,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(GetCommentsRequest $request): self
    {
        /** @var array<string, mixed> $defaults */
        $defaults = $request->withDefaults();

        return new self(
            search: isset($defaults['search']) ? (string) $defaults['search'] : null,
            status: isset($defaults['status']) ? CommentStatus::from((string) $defaults['status']) : null,
            userId: isset($defaults['user_id']) ? (int) $defaults['user_id'] : null,
            articleId: isset($defaults['article_id']) ? (int) $defaults['article_id'] : null,
            sortBy: (string) ($defaults['sort_by'] ?? 'created_at'),
            sortOrder: (string) ($defaults['sort_order'] ?? 'desc'),
            perPage: (int) ($defaults['per_page'] ?? 15),
        );
    }

    /**
     * Convert to array for query building
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'search' => $this->search,
            'user_id' => $this->userId,
            'article_id' => $this->articleId,
            'parent_comment_id' => $this->parentCommentId,
            'approved_by' => $this->approvedBy,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
            'per_page' => $this->perPage,
        ];

        if ($this->status !== null) {
            $data['status'] = $this->status->value;
        }
        if ($this->hasReports !== null) {
            $data['has_reports'] = $this->hasReports;
        }

        return $data;
    }
}
