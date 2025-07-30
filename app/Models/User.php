<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property string|null $avatar_url
 * @property string|null $bio
 * @property string|null $twitter
 * @property string|null $facebook
 * @property string|null $linkedin
 * @property string|null $github
 * @property string|null $website
 * @property string|null $token Dynamic property set by auth service

 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property (\Illuminate\Support\Carbon|\Carbon\CarbonImmutable)|null $access_token_expires_at
 * @property (\Illuminate\Support\Carbon|\Carbon\CarbonImmutable)|null $refresh_token_expires_at
 *
 * @mixin \Eloquent
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<self>
 */
final class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The roles that belong to the user.
     *
     * @return BelongsToMany<Role, User, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function roles(): BelongsToMany
    {
        /** @var BelongsToMany<Role, User, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'> $relation */
        $relation = $this->belongsToMany(Role::class);

        return $relation;
    }

    /**
     * Check if the user has a given role.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles->pluck('name')->contains($role);
    }

    /**
     * Check if the user has a given permission via their roles.
     */
    public function hasPermission(string $permission): bool
    {
        foreach ($this->roles as $role) {
            /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission> $permissions */
            $permissions = $role->permissions;
            if ($permissions->contains('name', $permission)) {
                return true;
            }
        }

        return false;
    }
}
