<?php

declare(strict_types=1);

namespace App\Data\User;

use App\Http\Requests\V1\User\GetUserFollowersRequest;
use App\Http\Requests\V1\User\GetUserFollowingRequest;

/**
 * Data Transfer Object for filtering user followers/following
 */
final class FilterUserFollowersDTO
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 15,
        public readonly string $sortBy = 'created_at',
        public readonly string $sortDirection = 'desc',
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(GetUserFollowersRequest|GetUserFollowingRequest $request): self
    {
        $defaults = $request->withDefaults();

        return new self(
            page: (int) ($defaults['page'] ?? 1),
            perPage: (int) ($defaults['per_page'] ?? 15),
            sortBy: (string) ($defaults['sort_by'] ?? 'created_at'),
            sortDirection: (string) ($defaults['sort_direction'] ?? 'desc'),
        );
    }

    /**
     * Convert to array for query building
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->perPage,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
        ];
    }
}
