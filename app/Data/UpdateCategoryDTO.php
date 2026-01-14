<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\V1\Admin\Category\UpdateCategoryRequest;
use Illuminate\Support\Str;

/**
 * Data Transfer Object for updating a category
 */
final class UpdateCategoryDTO
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $slug,
        public readonly ?int $parentId,
        public readonly bool $hasParentId, // Track if parent_id was explicitly provided
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(UpdateCategoryRequest $request): self
    {
        $validated = $request->validated();

        $name = isset($validated['name']) ? (string) $validated['name'] : null;
        $slug = isset($validated['slug']) ? (string) $validated['slug'] : null;

        // If name is provided but slug is not, generate slug from name
        if ($name !== null && $slug === null) {
            $slug = Str::slug($name);
        }

        $hasParentId = array_key_exists('parent_id', $validated);
        $parentId = $hasParentId ? ($validated['parent_id'] !== null ? (int) $validated['parent_id'] : null) : null;

        return new self(
            name: $name,
            slug: $slug,
            parentId: $parentId,
            hasParentId: $hasParentId,
        );
    }

    /**
     * Convert to array for database operations (only non-null values, except parent_id which can be null)
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->slug !== null) {
            $data['slug'] = $this->slug;
        }

        // Include parent_id if it was explicitly provided (even if null)
        if ($this->hasParentId) {
            $data['parent_id'] = $this->parentId;
        }

        return $data;
    }
}
