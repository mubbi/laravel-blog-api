<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string|null $subtitle
 * @property string|null $excerpt
 * @property string $content_markdown
 * @property string|null $content_html
 * @property string|null $featured_image
 * @property ArticleStatus $status
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property int $created_by
 * @property int $approved_by
 * @property int|null $updated_by
 *
 * @mixin \Eloquent
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<self>
 */
final class Article extends Model
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
        return [
            'published_at' => 'datetime',
            'status' => ArticleStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, Article>
     */
    public function author(): BelongsTo
    {
        /** @var BelongsTo<User, Article> $relation */
        $relation = $this->belongsTo(User::class, 'created_by');

        return $relation;
    }

    /**
     * @return BelongsTo<User, Article>
     */
    public function approver(): BelongsTo
    {
        /** @var BelongsTo<User, Article> $relation */
        $relation = $this->belongsTo(User::class, 'approved_by');

        return $relation;
    }

    /**
     * @return BelongsTo<User, Article>
     */
    public function updater(): BelongsTo
    {
        /** @var BelongsTo<User, Article> $relation */
        $relation = $this->belongsTo(User::class, 'updated_by');

        return $relation;
    }

    /**
     * @return HasMany<Comment, Article>
     */
    public function comments(): HasMany
    {
        /** @var HasMany<Comment, Article> $relation */
        $relation = $this->hasMany(Comment::class);

        return $relation;
    }

    /**
     * @return BelongsToMany<Category, Article, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function categories(): BelongsToMany
    {
        /** @var BelongsToMany<Category, Article, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'> $relation */
        $relation = $this->belongsToMany(Category::class, 'article_categories');

        return $relation;
    }

    /**
     * @return BelongsToMany<Tag, Article, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function tags(): BelongsToMany
    {
        /** @var BelongsToMany<Tag, Article, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'> $relation */
        $relation = $this->belongsToMany(Tag::class, 'article_tags');

        return $relation;
    }

    /**
     * @return BelongsToMany<User, Article, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function authors(): BelongsToMany
    {
        /** @var BelongsToMany<User, Article, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'> $relation */
        $relation = $this->belongsToMany(User::class, 'article_authors')->withPivot('role');

        return $relation;
    }
}
