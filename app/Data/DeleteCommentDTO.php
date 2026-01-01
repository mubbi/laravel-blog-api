<?php

declare(strict_types=1);

namespace App\Data;

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
     */
    public static function fromRequest(\App\Http\Requests\V1\Admin\Comment\DeleteCommentRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            reason: isset($validated['reason']) ? (string) $validated['reason'] : null,
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
