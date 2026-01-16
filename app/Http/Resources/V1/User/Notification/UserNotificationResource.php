<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\User\Notification;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read UserNotification $resource
 */
class UserNotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'notification_id' => $this->resource->notification_id,
            'is_read' => $this->resource->is_read,
            'created_at' => $this->resource->created_at->toISOString(),
            'updated_at' => $this->resource->updated_at->toISOString(),
            'notification' => $this->whenLoaded('notification', function () {
                return [
                    'id' => $this->resource->notification->id,
                    'type' => $this->resource->notification->type->value,
                    'message' => $this->resource->notification->message,
                    'created_at' => $this->resource->notification->created_at->toISOString(),
                    'updated_at' => $this->resource->notification->updated_at->toISOString(),
                ];
            }),
        ];
    }
}
