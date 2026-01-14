<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\User;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read User $resource
 */
final class UserResource extends JsonResource
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
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'avatar_url' => $this->resource->avatar_url,
            'bio' => $this->resource->bio,
            'twitter' => $this->resource->twitter,
            'facebook' => $this->resource->facebook,
            'linkedin' => $this->resource->linkedin,
            'github' => $this->resource->github,
            'website' => $this->resource->website,
            'banned_at' => $this->resource->banned_at,
            'blocked_at' => $this->resource->blocked_at,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'articles_count' => $this->when(
                isset($this->resource->articles_count),
                fn () => $this->resource->articles_count
            ),
            'comments_count' => $this->when(
                isset($this->resource->comments_count),
                fn () => $this->resource->comments_count
            ),
            'followers_count' => $this->when(
                isset($this->resource->followers_count),
                fn () => $this->resource->followers_count
            ),
            'following_count' => $this->when(
                isset($this->resource->following_count),
                fn () => $this->resource->following_count
            ),
            'roles' => $this->whenLoaded('roles', function () {
                return $this->resource->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'slug' => $role->slug,
                    ];
                });
            }),
        ];
    }
}
