<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $article_id
 * @property int|null $user_id
 * @property string $content
 * @property int|null $parent_comment_id
 * @property CommentStatus $status
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int|null $approved_by
 * @property int $report_count
 * @property \Illuminate\Support\Carbon|null $last_reported_at
 * @property string|null $report_reason
 * @property string|null $moderator_notes
 * @property string|null $admin_note
 * @property string|null $deleted_reason
 * @property int|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read int $replies_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Comment>|null $replies_page
 * @property-read Article $article
 * @property-read User|null $user
 * @property-read User|null $approver
 * @property-read User|null $deletedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Comment> $replies
 *
 * @mixin \Eloquent
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<self>
 */
class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CommentStatus::class,
            'approved_at' => 'datetime',
            'last_reported_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
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

    /**
     * @return BelongsTo<User, Comment>
     */
    public function approver(): BelongsTo
    {
        /** @var BelongsTo<User, Comment> $relation */
        $relation = $this->belongsTo(User::class, 'approved_by');

        return $relation;
    }

    /**
     * @return BelongsTo<User, Comment>
     */
    public function deletedBy(): BelongsTo
    {
        /** @var BelongsTo<User, Comment> $relation */
        $relation = $this->belongsTo(User::class, 'deleted_by');

        return $relation;
    }
}
