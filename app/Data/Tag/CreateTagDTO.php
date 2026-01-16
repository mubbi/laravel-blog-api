<?php

declare(strict_types=1);

namespace App\Data\Tag;

use App\Http\Requests\V1\Tag\CreateTagRequest;
use Illuminate\Support\Str;

/**
 * Data Transfer Object for creating a tag
 */
final class CreateTagDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(CreateTagRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: (string) $validated['name'],
            slug: isset($validated['slug']) ? (string) $validated['slug'] : Str::slug((string) $validated['name']),
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
        ];
    }
}
