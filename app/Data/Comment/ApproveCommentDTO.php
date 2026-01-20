<?php

declare(strict_types=1);

namespace App\Data\Comment;

use App\Http\Requests\V1\Comment\ApproveCommentRequest;

/**
 * Data Transfer Object for approving a comment
 */
final class ApproveCommentDTO
{
    public function __construct(
        public readonly ?string $adminNote = null,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(ApproveCommentRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            adminNote: isset($validated['admin_note']) ? (string) $validated['admin_note'] : null,
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
            adminNote: isset($data['admin_note']) ? (string) $data['admin_note'] : null,
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

        if ($this->adminNote !== null) {
            $data['admin_note'] = $this->adminNote;
        }

        return $data;
    }
}
