<?php

declare(strict_types=1);

namespace App\Data\Media;

use App\Http\Requests\V1\Media\GetMediaLibraryRequest;

/**
 * Data Transfer Object for filtering media
 */
final class FilterMediaDTO
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 15,
        public readonly string $sortBy = 'created_at',
        public readonly string $sortDirection = 'desc',
        public readonly ?string $type = null,
        public readonly ?string $search = null,
        public readonly ?int $uploadedBy = null,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(GetMediaLibraryRequest $request): self
    {
        return self::fromArray($request->validated());
    }

    /**
     * Create DTO from array
     *
     * @param  array<string, mixed>  $params
     */
    public static function fromArray(array $params): self
    {
        return new self(
            page: isset($params['page']) ? (int) $params['page'] : 1,
            perPage: isset($params['per_page']) ? (int) $params['per_page'] : 15,
            sortBy: isset($params['sort_by']) ? (string) $params['sort_by'] : 'created_at',
            sortDirection: isset($params['sort_direction']) ? (string) $params['sort_direction'] : 'desc',
            type: isset($params['type']) && $params['type'] !== '' ? (string) $params['type'] : null,
            search: isset($params['search']) && $params['search'] !== '' ? (string) $params['search'] : null,
            uploadedBy: isset($params['uploaded_by']) ? (int) $params['uploaded_by'] : null,
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
            'page' => $this->page,
            'per_page' => $this->perPage,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
        ];

        if ($this->type !== null) {
            $data['type'] = $this->type;
        }

        if ($this->search !== null) {
            $data['search'] = $this->search;
        }

        if ($this->uploadedBy !== null) {
            $data['uploaded_by'] = $this->uploadedBy;
        }

        return $data;
    }

    /**
     * Create a new DTO with updated uploadedBy value
     */
    public function withUploadedBy(?int $uploadedBy): self
    {
        return new self(
            page: $this->page,
            perPage: $this->perPage,
            sortBy: $this->sortBy,
            sortDirection: $this->sortDirection,
            type: $this->type,
            search: $this->search,
            uploadedBy: $uploadedBy,
        );
    }
}
