<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ArticleAuthorRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $article_id
 * @property int $user_id
 * @property ArticleAuthorRole|null $role
 *
 * @mixin \Eloquent
 *
 * @use HasFactory<ArticleAuthor>
 *
 * @phpstan-use HasFactory<ArticleAuthor>
 */
final class ArticleAuthor extends Model
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
        return [
            'role' => ArticleAuthorRole::class,
        ];
    }

    /**
     * @return BelongsTo<Article,ArticleAuthor>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * @return BelongsTo<User,ArticleAuthor>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
