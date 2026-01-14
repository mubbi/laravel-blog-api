<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\V1\Comment\UpdateCommentRequest;

/**
 * Data Transfer Object for updating a comment
 */
final class UpdateCommentDTO
{
    public function __construct(
        public readonly string $content,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(UpdateCommentRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            content: (string) $validated['content'],
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
            content: (string) $data['content'],
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
            'content' => $this->content,
        ];
    }
}
