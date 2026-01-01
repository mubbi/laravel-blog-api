<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\NotificationType;

/**
 * Data Transfer Object for filtering notifications
 */
final class FilterNotificationDTO
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?NotificationType $type = null,
        public readonly ?string $createdAtFrom = null,
        public readonly ?string $createdAtTo = null,
        public readonly string $sortBy = 'created_at',
        public readonly string $sortOrder = 'desc',
        public readonly int $perPage = 15,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(\App\Http\Requests\V1\Admin\Notification\GetNotificationsRequest $request): self
    {
        $defaults = $request->withDefaults();

        return new self(
            search: isset($defaults['search']) ? (string) $defaults['search'] : null,
            type: isset($defaults['type']) ? NotificationType::from((string) $defaults['type']) : null,
            createdAtFrom: isset($defaults['created_at_from']) ? (string) $defaults['created_at_from'] : null,
            createdAtTo: isset($defaults['created_at_to']) ? (string) $defaults['created_at_to'] : null,
            sortBy: (string) ($defaults['sort_by'] ?? 'created_at'),
            sortOrder: (string) ($defaults['sort_order'] ?? 'desc'),
            perPage: (int) ($defaults['per_page'] ?? 15),
        );
    }

    /**
     * Convert to array for query building
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'search' => $this->search,
            'created_at_from' => $this->createdAtFrom,
            'created_at_to' => $this->createdAtTo,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
            'per_page' => $this->perPage,
        ];

        if ($this->type !== null) {
            $data['type'] = $this->type->value;
        }

        return $data;
    }
}
