<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\V1\Admin\Category\CreateCategoryRequest;
use Illuminate\Support\Str;

/**
 * Data Transfer Object for creating a category
 */
final class CreateCategoryDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly ?int $parentId,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(CreateCategoryRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: (string) $validated['name'],
            slug: isset($validated['slug']) ? (string) $validated['slug'] : Str::slug((string) $validated['name']),
            parentId: isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
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
            'name' => $this->name,
            'slug' => $this->slug,
            'parent_id' => $this->parentId,
        ];
    }
}
