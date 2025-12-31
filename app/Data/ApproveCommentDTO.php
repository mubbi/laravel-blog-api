<?php

declare(strict_types=1);

namespace App\Data;

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
    public static function fromRequest(\App\Http\Requests\V1\Admin\Comment\ApproveCommentRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            adminNote: isset($validated['admin_note']) ? (string) $validated['admin_note'] : null,
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
