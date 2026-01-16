<?php

declare(strict_types=1);

namespace App\Data\Media;

use App\Http\Requests\V1\Media\UploadMediaRequest;

/**
 * Data Transfer Object for uploading media
 */
final class UploadMediaDTO
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $altText,
        public readonly ?string $caption,
        public readonly ?string $description,
        public readonly string $disk,
        public readonly int $uploadedBy,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(UploadMediaRequest $request): self
    {
        $validated = $request->validated();
        $user = $request->user();
        assert($user !== null);

        return new self(
            name: isset($validated['name']) ? (string) $validated['name'] : null,
            altText: isset($validated['alt_text']) ? (string) $validated['alt_text'] : null,
            caption: isset($validated['caption']) ? (string) $validated['caption'] : null,
            description: isset($validated['description']) ? (string) $validated['description'] : null,
            disk: isset($validated['disk']) ? (string) $validated['disk'] : 'public',
            uploadedBy: $user->id,
        );
    }

    /**
     * Convert to array for service operations
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'disk' => $this->disk,
            'uploaded_by' => $this->uploadedBy,
        ];

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
