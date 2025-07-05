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
 *
 * @mixin \Eloquent
 *
 * @use HasFactory<Role>
 *
 * @phpstan-use HasFactory<Role>
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
}
