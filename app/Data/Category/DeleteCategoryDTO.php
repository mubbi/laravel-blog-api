<?php

declare(strict_types=1);

namespace App\Data\Category;

use App\Http\Requests\V1\Category\DeleteCategoryRequest;

/**
 * Data Transfer Object for deleting a category
 */
final class DeleteCategoryDTO
{
    public function __construct(
        public readonly bool $deleteChildren,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(DeleteCategoryRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            deleteChildren: isset($validated['delete_children']) && (bool) $validated['delete_children'],
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
            'delete_children' => $this->deleteChildren,
        ];
    }
}
