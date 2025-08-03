<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Permission> $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 *
 * @mixin \Eloquent
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<self>
 */
final class Role extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * The permissions that belong to the role.
     *
     * @return BelongsToMany<Permission, Role, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function permissions(): BelongsToMany
    {
        /** @var BelongsToMany<Permission, Role, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'> $relation */
        $relation = $this->belongsToMany(Permission::class);

        return $relation;
    }

    /**
     * The users that belong to the role.
     *
     * @return BelongsToMany<User, Role, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function users(): BelongsToMany
    {
        /** @var BelongsToMany<User, Role, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'> $relation */
        $relation = $this->belongsToMany(User::class);

        return $relation;
    }
}
