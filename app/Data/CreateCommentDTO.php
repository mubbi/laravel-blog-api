<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\V1\Comment\CreateCommentRequest;
use App\Models\Article;

/**
 * Data Transfer Object for creating a comment
 */
final class CreateCommentDTO
{
    public function __construct(
        public readonly int $articleId,
        public readonly string $content,
        public readonly ?int $parentCommentId = null,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(CreateCommentRequest $request, Article $article): self
    {
        $validated = $request->validated();

        return new self(
            articleId: $article->id,
            content: (string) $validated['content'],
            parentCommentId: isset($validated['parent_comment_id']) ? (int) $validated['parent_comment_id'] : null,
        );
    }

    /**
     * Create DTO from array
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            articleId: (int) $data['article_id'],
            content: (string) $data['content'],
            parentCommentId: isset($data['parent_comment_id']) ? (int) $data['parent_comment_id'] : null,
        );
    }

    /**
     * Convert to array for database operations
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'article_id' => $this->articleId,
            'content' => $this->content,
        ];

        if ($this->parentCommentId !== null) {
            $data['parent_comment_id'] = $this->parentCommentId;
        }

        return $data;
    }
}
