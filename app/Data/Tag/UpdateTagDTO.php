<?php

declare(strict_types=1);

namespace App\Data\Tag;

use App\Http\Requests\V1\Tag\UpdateTagRequest;
use Illuminate\Support\Str;

/**
 * Data Transfer Object for updating a tag
 */
final class UpdateTagDTO
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $slug,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(UpdateTagRequest $request): self
    {
        $validated = $request->validated();

        $name = isset($validated['name']) ? (string) $validated['name'] : null;
        $slug = isset($validated['slug']) ? (string) $validated['slug'] : null;

        // If name is provided but slug is not, generate slug from name
        if ($name !== null && $slug === null) {
            $slug = Str::slug($name);
        }

        return new self(
            name: $name,
            slug: $slug,
        );
    }

    /**
     * Convert to array for database operations (only non-null values)
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

        return $data;
    }
}
