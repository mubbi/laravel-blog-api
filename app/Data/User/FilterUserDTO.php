<?php

declare(strict_types=1);

namespace App\Data\User;

use App\Http\Requests\V1\User\GetUsersRequest;

/**
 * Data Transfer Object for filtering users
 */
final class FilterUserDTO
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 15,
        public readonly ?string $search = null,
        public readonly ?int $roleId = null,
        public readonly ?string $status = null,
        public readonly ?string $createdAfter = null,
        public readonly ?string $createdBefore = null,
        public readonly string $sortBy = 'created_at',
        public readonly string $sortDirection = 'desc',
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(GetUsersRequest $request): self
    {
        $defaults = $request->withDefaults();

        return new self(
            page: (int) ($defaults['page'] ?? 1),
            perPage: (int) ($defaults['per_page'] ?? 15),
            search: isset($defaults['search']) ? (string) $defaults['search'] : null,
            roleId: isset($defaults['role_id']) ? (int) $defaults['role_id'] : null,
            status: isset($defaults['status']) ? (string) $defaults['status'] : null,
            createdAfter: isset($defaults['created_after']) ? (string) $defaults['created_after'] : null,
            createdBefore: isset($defaults['created_before']) ? (string) $defaults['created_before'] : null,
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
            'search' => $this->search,
            'role_id' => $this->roleId,
            'status' => $this->status,
            'created_after' => $this->createdAfter,
            'created_before' => $this->createdBefore,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
        ];
    }
}
