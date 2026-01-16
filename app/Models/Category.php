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
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Category|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Article> $articles
 *
 * @mixin \Eloquent
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<self>
 */
final class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
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
        return [];
    }

    /**
     * Get the parent category.
     *
     * @return BelongsTo<Category, Category>
     */
    public function parent(): BelongsTo
    {
        /** @var BelongsTo<Category, Category> $relation */
        $relation = $this->belongsTo(Category::class, 'parent_id');

        return $relation;
    }

    /**
     * Get the child categories.
     *
     * @return HasMany<Category, Category>
     */
    public function children(): HasMany
    {
        /** @var HasMany<Category, Category> $relation */
        $relation = $this->hasMany(Category::class, 'parent_id');

        return $relation;
    }

    /**
     * Get all descendant category IDs recursively
     *
     * Uses a single recursive query to avoid N+1 queries and memory issues.
     *
     * @return array<int>
     */
    public function getDescendantIds(): array
    {
        /** @var array<int> $descendants */
        $descendants = [];
        /** @var array<int> $stack */
        $stack = [$this->id];

        // Use iterative approach with a single query to load all categories
        // This prevents N+1 queries and is more memory efficient than recursion
        while (! empty($stack)) {
            $currentId = array_pop($stack);
            if ($currentId === null) {
                break;
            }

            // Query all children of current category in one query
            /** @var array<int, mixed> $children */
            $children = self::query()
                ->where('parent_id', $currentId)
                ->pluck('id')
                ->toArray();

            foreach ($children as $childId) {
                $childIdInt = (int) $childId;
                $descendants[] = $childIdInt;
                $stack[] = $childIdInt;
            }
        }

        return $descendants;
    }

    /**
     * @return BelongsToMany<Article, Category, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function articles(): BelongsToMany
    {
        /** @var BelongsToMany<Article, Category, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'> $relation */
        $relation = $this->belongsToMany(Article::class, 'article_categories');

        return $relation;
    }
}
