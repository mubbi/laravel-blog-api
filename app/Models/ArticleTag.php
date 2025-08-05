<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $article_id
 * @property int $tag_id
 * @property-read Article $article
 * @property-read Tag $tag
 *
 * @mixin \Eloquent
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<self>
 */
final class ArticleTag extends Model
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
     * @return BelongsTo<Article, ArticleTag>
     */
    public function article(): BelongsTo
    {
        /** @var BelongsTo<Article, ArticleTag> $relation */
        $relation = $this->belongsTo(Article::class);

        return $relation;
    }

    /**
     * @return BelongsTo<Tag, ArticleTag>
     */
    public function tag(): BelongsTo
    {
        /** @var BelongsTo<Tag, ArticleTag> $relation */
        $relation = $this->belongsTo(Tag::class);

        return $relation;
    }
}
