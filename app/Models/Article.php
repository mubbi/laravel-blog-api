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
 * @property int|null $featured_media_id
 * @property ArticleStatus $status
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property int $created_by
 * @property int|null $approved_by
 * @property int|null $updated_by
 * @property bool $is_featured
 * @property bool $is_pinned
 * @property \Illuminate\Support\Carbon|null $featured_at
 * @property \Illuminate\Support\Carbon|null $pinned_at
 * @property int $report_count
 * @property \Illuminate\Support\Carbon|null $last_reported_at
 * @property string|null $report_reason
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $author
 * @property-read User|null $approver
 * @property-read User|null $updater
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Comment> $comments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $categories
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Tag> $tags
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $authors
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ArticleLike> $likes
 * @property-read Media|null $featuredMedia
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Media> $media
 *
 * @mixin \Eloquent
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<self>
 */
final class Article extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'subtitle',
        'excerpt',
        'content_markdown',
        'content_html',
        'featured_media_id',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
        'is_featured',
        'is_pinned',
        'featured_at',
        'pinned_at',
    ];

    /**
     * The attributes that should be guarded from mass assignment.
     *
     * @var list<string>
     */
    protected $guarded = [
        'id',
        'created_by',
        'approved_by',
        'updated_by',
        'report_count',
        'last_reported_at',
        'report_reason',
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
            'published_at' => 'datetime',
            'status' => ArticleStatus::class,
            'featured_at' => 'datetime',
            'pinned_at' => 'datetime',
            'last_reported_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_pinned' => 'boolean',
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

    /**
     * @return HasMany<ArticleLike, Article>
     */
    public function likes(): HasMany
    {
        /** @var HasMany<ArticleLike, Article> $relation */
        $relation = $this->hasMany(ArticleLike::class);

        return $relation;
    }

    /**
     * Get the featured media for the article.
     *
     * @return BelongsTo<Media, Article>
     */
    public function featuredMedia(): BelongsTo
    {
        /** @var BelongsTo<Media, Article> $relation */
        $relation = $this->belongsTo(Media::class, 'featured_media_id');

        return $relation;
    }

    /**
     * Get the media attached to the article.
     *
     * @return BelongsToMany<Media, Article, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function media(): BelongsToMany
    {
        /** @var BelongsToMany<Media, Article, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'> $relation */
        $relation = $this->belongsToMany(Media::class, 'article_media')
            ->withPivot('usage_type', 'order')
            ->withTimestamps()
            ->orderByPivot('order');

        return $relation;
    }

    /**
     * Scope a query to only include published articles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Article>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Article>
     */
    public function scopePublished($query)
    {
        return $query->where('status', ArticleStatus::PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope a query to only include draft articles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Article>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Article>
     */
    public function scopeDraft($query)
    {
        return $query->where('status', ArticleStatus::DRAFT);
    }

    /**
     * Scope a query to only include articles in review.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Article>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Article>
     */
    public function scopeInReview($query)
    {
        return $query->where('status', ArticleStatus::REVIEW);
    }

    /**
     * Scope a query to only include archived articles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Article>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Article>
     */
    public function scopeArchived($query)
    {
        return $query->where('status', ArticleStatus::ARCHIVED);
    }

    /**
     * Scope a query to only include featured articles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Article>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Article>
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include pinned articles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Article>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Article>
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope a query to filter articles by category slug.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Article>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Article>
     */
    public function scopeByCategory($query, string $slug)
    {
        return $query->whereHas('categories', function ($q) use ($slug) {
            $q->where('slug', $slug);
        });
    }

    /**
     * Scope a query to filter articles by tag slug.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Article>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Article>
     */
    public function scopeByTag($query, string $slug)
    {
        return $query->whereHas('tags', function ($q) use ($slug) {
            $q->where('slug', $slug);
        });
    }

    /**
     * Scope a query to filter articles by author ID.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Article>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Article>
     */
    public function scopeByAuthor($query, int $authorId)
    {
        return $query->whereHas('authors', function ($q) use ($authorId) {
            $q->where('user_id', $authorId);
        });
    }

    /**
     * Scope a query to search articles by title, subtitle, excerpt, or content.
     * Uses full-text index for title, subtitle, excerpt when possible, falls back to LIKE for content_markdown.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Article>  $query
     * @param  string  $search  The search term
     * @return \Illuminate\Database\Eloquent\Builder<Article>
     */
    public function scopeSearch($query, string $search)
    {
        // Use full-text search for indexed columns (title, subtitle, excerpt)
        // Full-text indexes exist on: title, subtitle, excerpt (composite) and content_markdown (separate)
        return $query->whereFullText(['title', 'subtitle', 'excerpt'], $search)
            ->orWhereFullText('content_markdown', $search);
    }
}
