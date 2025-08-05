<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Newsletter;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read NewsletterSubscriber $resource
 */
class NewsletterSubscriberResource extends JsonResource
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
            'email' => $this->resource->email,
            'user_id' => $this->resource->user_id,
            'is_verified' => $this->resource->is_verified,
            'subscribed_at' => $this->resource->subscribed_at->toISOString(),
            'created_at' => $this->resource->created_at->toISOString(),
            'updated_at' => $this->resource->updated_at->toISOString(),
        ];
    }
}
