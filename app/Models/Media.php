<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $file_name
 * @property string $mime_type
 * @property string $disk
 * @property string $path
 * @property string|null $url
 * @property int $size
 * @property string $type
 * @property string|null $alt_text
 * @property string|null $caption
 * @property string|null $description
 * @property array<string, mixed>|null $metadata
 * @property int $uploaded_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $uploader
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Article> $articles
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Article> $featuredInArticles
 *
 * @mixin \Eloquent
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<self>
 */
final class Media extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'file_name',
        'mime_type',
        'disk',
        'path',
        'url',
        'size',
        'type',
        'alt_text',
        'caption',
        'description',
        'metadata',
    ];

    /**
     * The attributes that should be guarded from mass assignment.
     *
     * @var list<string>
     */
    protected $guarded = [
        'id',
        'uploaded_by',
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
            'metadata' => 'array',
            'size' => 'integer',
        ];
    }

    /**
     * Get the user who uploaded the media.
     *
     * @return BelongsTo<User, Media>
     */
    public function uploader(): BelongsTo
    {
        /** @var BelongsTo<User, Media> $relation */
        $relation = $this->belongsTo(User::class, 'uploaded_by');

        return $relation;
    }

    /**
     * Get the articles that use this media as featured image.
     *
     * @return HasMany<Article, Media>
     */
    public function featuredInArticles(): HasMany
    {
        /** @var HasMany<Article, Media> $relation */
        $relation = $this->hasMany(Article::class, 'featured_media_id');

        return $relation;
    }

    /**
     * Get the articles that have this media attached.
     *
     * @return BelongsToMany<Article, Media, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function articles(): BelongsToMany
    {
        /** @var BelongsToMany<Article, Media, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'> $relation */
        $relation = $this->belongsToMany(Article::class, 'article_media')
            ->withPivot('usage_type', 'order')
            ->withTimestamps()
            ->orderByPivot('order');

        return $relation;
    }
}
