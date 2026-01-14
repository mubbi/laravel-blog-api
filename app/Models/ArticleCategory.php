<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $article_id
 * @property int $category_id
 * @property-read Article $article
 * @property-read Category $category
 *
 * @mixin \Eloquent
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<self>
 */
final class ArticleCategory extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'article_id',
        'category_id',
    ];

    /**
     * The attributes that should be guarded from mass assignment.
     *
     * @var list<string>
     */
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
