<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Admin\User;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read User $resource
 */
final class UserDetailResource extends JsonResource
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
            'email_verified_at' => $this->resource->email_verified_at,
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
            'roles' => $this->whenLoaded('roles', function () {
                return $this->resource->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'slug' => $role->slug,
                        'display_name' => UserRole::from($role->name)->displayName(),
                    ];
                });
            }),
            'articles_count' => $this->resource->articles_count,
            'comments_count' => $this->resource->comments_count,
            'status' => $this->getUserStatus(),
        ];
    }

    /**
     * Get user status based on banned/blocked fields
     */
    private function getUserStatus(): string
    {
        if ($this->resource->banned_at) {
            return 'banned';
        }

        if ($this->resource->blocked_at) {
            return 'blocked';
        }

        return 'active';
    }
}
