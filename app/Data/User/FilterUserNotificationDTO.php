<?php

declare(strict_types=1);

namespace App\Data\User;

use App\Enums\NotificationType;
use App\Http\Requests\V1\User\Notification\GetUserNotificationsRequest;

/**
 * Data Transfer Object for filtering user notifications
 */
final class FilterUserNotificationDTO
{
    public function __construct(
        public readonly ?bool $isRead = null,
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
    public static function fromRequest(GetUserNotificationsRequest $request): self
    {
        /** @var array<string, mixed> $defaults */
        $defaults = $request->withDefaults();

        return new self(
            isRead: isset($defaults['is_read']) ? (bool) $defaults['is_read'] : null,
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
        return [
            'is_read' => $this->isRead,
            'type' => $this->type?->value,
            'created_at_from' => $this->createdAtFrom,
            'created_at_to' => $this->createdAtTo,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
            'per_page' => $this->perPage,
        ];
    }
}
