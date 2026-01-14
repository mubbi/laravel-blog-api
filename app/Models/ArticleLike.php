<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ArticleReactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $article_id
 * @property int|null $user_id
 * @property string|null $ip_address
 * @property ArticleReactionType $type
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Article $article
 * @property-read User|null $user
 *
 * @mixin \Eloquent
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<self>
 */
final class ArticleLike extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'article_id',
        'user_id',
        'ip_address',
        'type',
    ];

    /**
     * The attributes that should be guarded from mass assignment.
     *
     * @var list<string>
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ArticleReactionType::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Article, ArticleLike>
     */
    public function article(): BelongsTo
    {
        /** @var BelongsTo<Article, ArticleLike> $relation */
        $relation = $this->belongsTo(Article::class);

        return $relation;
    }

    /**
     * @return BelongsTo<User, ArticleLike>
     */
    public function user(): BelongsTo
    {
        /** @var BelongsTo<User, ArticleLike> $relation */
        $relation = $this->belongsTo(User::class);

        return $relation;
    }
}
