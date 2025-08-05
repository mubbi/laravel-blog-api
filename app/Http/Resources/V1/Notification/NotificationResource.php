<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Notification;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Notification $resource
 */
class NotificationResource extends JsonResource
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
            'type' => $this->resource->type,
            'message' => $this->resource->message,
            'created_at' => $this->resource->created_at->toISOString(),
            'updated_at' => $this->resource->updated_at->toISOString(),
            'audiences' => $this->whenLoaded('audiences', function () {
                return $this->resource->audiences->map(function ($audience) {
                    return [
                        'id' => $audience->id,
                        'audience_type' => $audience->audience_type,
                        'audience_id' => $audience->audience_id,
                    ];
                });
            }),
        ];
    }
}
