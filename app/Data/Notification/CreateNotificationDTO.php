<?php

declare(strict_types=1);

namespace App\Data\Notification;

use App\Enums\NotificationType;
use App\Http\Requests\V1\Notification\CreateNotificationRequest;

/**
 * Data Transfer Object for creating a notification
 */
final class CreateNotificationDTO
{
    /**
     * @param  array<string, mixed>  $message
     * @param  array<string>  $audiences
     * @param  array<int>|null  $userIds
     */
    public function __construct(
        public readonly NotificationType $type,
        public readonly array $message,
        public readonly array $audiences,
        public readonly ?array $userIds = null,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(CreateNotificationRequest $request): self
    {
        $validated = $request->validated();

        /** @var array<string, mixed> $message */
        $message = $validated['message'];
        /** @var array<string> $audiences */
        $audiences = $validated['audiences'];
        /** @var array<int>|null $userIds */
        $userIds = isset($validated['user_ids']) && is_array($validated['user_ids'])
            ? array_map(fn ($id) => (int) $id, $validated['user_ids'])
            : null;

        return new self(
            type: NotificationType::from((string) $validated['type']),
            message: $message,
            audiences: $audiences,
            userIds: $userIds,
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
            'type' => $this->type->value,
            'message' => $this->message,
        ];
    }
}
