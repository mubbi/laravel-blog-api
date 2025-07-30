<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $email
 * @property int|null $user_id
 * @property bool $is_verified
 * @property \Illuminate\Support\Carbon $subscribed_at
 *
 * @mixin \Eloquent
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<self>
 */
final class NewsletterSubscriber extends Model
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
            'is_verified' => 'boolean',
            'subscribed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, NewsletterSubscriber>
     */
    public function user(): BelongsTo
    {
        /** @var BelongsTo<User, NewsletterSubscriber> $relation */
        $relation = $this->belongsTo(User::class);

        return $relation;
    }
}
