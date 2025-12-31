<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property NotificationType $type
 * @property array<string, mixed> $message
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, NotificationAudience> $audiences
 *
 * @mixin \Eloquent
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<self>
 */
final class Notification extends Model
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
            'message' => 'array',
            'type' => NotificationType::class,
        ];
    }

    /**
     * Get the audiences for the notification.
     *
     * @return HasMany<NotificationAudience, Notification>
     */
    public function audiences(): HasMany
    {
        /** @var HasMany<NotificationAudience, Notification> $relation */
        $relation = $this->hasMany(NotificationAudience::class);

        return $relation;
    }
}
