<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $notification_id
 * @property int $user_id
 *
 * @mixin \Eloquent
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<self>
 */
final class NotificationAudience extends Model
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
     * @return BelongsTo<Notification, NotificationAudience>
     */
    public function notification(): BelongsTo
    {
        /** @var BelongsTo<Notification, NotificationAudience> $relation */
        $relation = $this->belongsTo(Notification::class);

        return $relation;
    }

    /**
     * @return BelongsTo<User, NotificationAudience>
     */
    public function user(): BelongsTo
    {
        /** @var BelongsTo<User, NotificationAudience> $relation */
        $relation = $this->belongsTo(User::class);

        return $relation;
    }
}
