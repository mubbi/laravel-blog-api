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
     * @return BelongsToMany<Article,Category>
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_categories');
    }
}
