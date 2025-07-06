<?php

namespace App\Http\Resources\Auth\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read User $resource
 */
class UserResource extends JsonResource
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
            'bio' => $this->resource->bio,
            'avatar_url' => $this->resource->avatar_url,
            'twitter' => $this->resource->twitter,
            'facebook' => $this->resource->facebook,
            'linkedin' => $this->resource->linkedin,
            'github' => $this->resource->github,
            'website' => $this->resource->website,
            'roles' => $this->whenLoaded('roles', function () {
                return $this->resource->roles->pluck('slug');
            }),
            'permissions' => $this->whenLoaded('roles', function () {
                $permissionSlugs = [];

                /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles */
                $roles = $this->resource->roles;

                foreach ($roles as $role) {
                    /** @var \App\Models\Role $role */
                    foreach ($role->permissions as $permission) {
                        /** @var \App\Models\Permission $permission */
                        $permissionSlugs[] = $permission->slug;
                    }
                }

                return array_values(array_unique($permissionSlugs));
            }),
            $this->mergeWhen(isset($this->resource->access_token), [
                // @phpstan-ignore-next-line property.notFound
                'access_token' => $this->resource->access_token,
                // @phpstan-ignore-next-line property.notFound
                'refresh_token' => $this->resource->refresh_token,
                // @phpstan-ignore-next-line property.notFound,method.nonObject
                'access_token_expires_at' => $this->resource->access_token_expires_at?->toISOString(),
                // @phpstan-ignore-next-line property.notFound,method.nonObject
                'refresh_token_expires_at' => $this->resource->refresh_token_expires_at?->toISOString(),
                'token_type' => 'Bearer',
            ]),
            // Legacy support for old 'token' field
            $this->mergeWhen(isset($this->resource->token), [
                'token' => $this->resource->token,
            ]),
        ];
    }
}
