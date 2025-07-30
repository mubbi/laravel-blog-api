<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $article_id
 * @property int $category_id
 *
 * @mixin \Eloquent
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<self>
 */
final class ArticleCategory extends Model
{
    use HasFactory;

    public $timestamps = false;

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
     * @return BelongsTo<Article, ArticleCategory>
     */
    public function article(): BelongsTo
    {
        /** @var BelongsTo<Article, ArticleCategory> $relation */
        $relation = $this->belongsTo(Article::class);

        return $relation;
    }

    /**
     * @return BelongsTo<Category, ArticleCategory>
     */
    public function category(): BelongsTo
    {
        /** @var BelongsTo<Category, ArticleCategory> $relation */
        $relation = $this->belongsTo(Category::class);

        return $relation;
    }
}
