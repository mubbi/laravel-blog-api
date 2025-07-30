<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $article_id
 * @property int $user_id
 * @property string $content
 * @property int|null $parent_comment_id
 * @property-read int $replies_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comment>|null $replies_page
 *
 * @mixin \Eloquent
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<self>
 */
final class Comment extends Model
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
     * @return BelongsTo<Article, Comment>
     */
    public function article(): BelongsTo
    {
        /** @var BelongsTo<Article, Comment> $relation */
        $relation = $this->belongsTo(Article::class);

        return $relation;
    }

    /**
     * @return BelongsTo<User, Comment>
     */
    public function user(): BelongsTo
    {
        /** @var BelongsTo<User, Comment> $relation */
        $relation = $this->belongsTo(User::class);

        return $relation;
    }

    /**
     * Get the replies (child comments) for this comment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Comment, Comment>
     */
    public function replies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        /** @var \Illuminate\Database\Eloquent\Relations\HasMany<Comment, Comment> $relation */
        $relation = $this->hasMany(Comment::class, 'parent_comment_id');

        return $relation;
    }
}
