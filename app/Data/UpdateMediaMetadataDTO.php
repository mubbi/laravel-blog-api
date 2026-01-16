<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\V1\Media\UpdateMediaMetadataRequest;

/**
 * Data Transfer Object for updating media metadata
 */
final class UpdateMediaMetadataDTO
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $altText,
        public readonly ?string $caption,
        public readonly ?string $description,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(UpdateMediaMetadataRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: isset($validated['name']) ? (string) $validated['name'] : null,
            altText: isset($validated['alt_text']) ? (string) $validated['alt_text'] : null,
            caption: isset($validated['caption']) ? (string) $validated['caption'] : null,
            description: isset($validated['description']) ? (string) $validated['description'] : null,
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

        if ($this->altText !== null) {
            $data['alt_text'] = $this->altText;
        }

        if ($this->caption !== null) {
            $data['caption'] = $this->caption;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        return $data;
    }
}
