<?php

namespace App\Http\Resources\V1\Auth;

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
            }, function () {
                // Fallback to cached roles if not loaded
                return $this->resource->getCachedRoles();
            }),
            'permissions' => $this->whenLoaded('roles', function () {
                /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles */
                $roles = $this->resource->roles;

                // Check if permissions are already loaded on roles to avoid N+1 queries
                $firstRole = $roles->first();
                if ($firstRole !== null && ! $firstRole->relationLoaded('permissions')) {
                    // Load permissions for all roles in one query to prevent N+1
                    $roles->load('permissions:id,name,slug');
                }

                $permissionSlugs = [];
                foreach ($roles as $role) {
                    foreach ($role->permissions as $permission) {
                        /** @var \App\Models\Permission $permission */
                        $permissionSlugs[] = $permission->slug;
                    }
                }

                return array_values(array_unique($permissionSlugs));
            }, function () {
                // Fallback to cached permissions if not loaded
                return $this->resource->getCachedPermissions();
            }),
            $this->mergeWhen(
                array_key_exists('access_token', $this->resource->getAttributes()),
                fn () => [
                    'access_token' => $this->resource->getAttributes()['access_token'],
                    'refresh_token' => $this->resource->getAttributes()['refresh_token'] ?? null,
                    'access_token_expires_at' => $this->formatDateTime($this->resource->getAttributes()['access_token_expires_at'] ?? null),
                    'refresh_token_expires_at' => $this->formatDateTime($this->resource->getAttributes()['refresh_token_expires_at'] ?? null),
                    'token_type' => 'Bearer',
                ]
            ),
        ];
    }

    /**
     * Format a datetime value to ISO string if it's a DateTimeInterface.
     */
    private function formatDateTime(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d\TH:i:s.v\Z');
        }

        return $value;
    }
}
