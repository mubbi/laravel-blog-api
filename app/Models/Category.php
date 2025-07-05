<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 *
 * @mixin \Eloquent
 *
 * @phpstan-use HasFactory<Category>
 */
final class Category extends Model
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
     * @return HasMany<ArticleCategory,Category>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(ArticleCategory::class);
    }
}
