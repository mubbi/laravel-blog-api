<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\V1\Admin\Comment\DeleteCommentRequest as AdminDeleteCommentRequest;
use App\Http\Requests\V1\Comment\DeleteCommentRequest as CommentDeleteCommentRequest;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Data Transfer Object for deleting a comment
 */
final class DeleteCommentDTO
{
    public function __construct(
        public readonly ?string $reason = null,
    ) {}

    /**
     * Create DTO from request
     *
     * @param  AdminDeleteCommentRequest|CommentDeleteCommentRequest  $request
     */
    public static function fromRequest(FormRequest $request): self
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        return new self(
            reason: isset($validated['reason']) ? (string) $validated['reason'] : null,
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
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
        );
    }

    /**
     * Convert to array for database operations
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->reason !== null) {
            $data['reason'] = $this->reason;
        }

        return $data;
    }
}
