<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $notification_id
 * @property bool $is_read
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 * @property-read Notification $notification
 *
 * @mixin \Eloquent
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<self>
 */
final class UserNotification extends Model
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
            'is_read' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, UserNotification>
     */
    public function user(): BelongsTo
    {
        /** @var BelongsTo<User, UserNotification> $relation */
        $relation = $this->belongsTo(User::class);

        return $relation;
    }

    /**
     * @return BelongsTo<Notification, UserNotification>
     */
    public function notification(): BelongsTo
    {
        /** @var BelongsTo<Notification, UserNotification> $relation */
        $relation = $this->belongsTo(Notification::class);

        return $relation;
    }
}
