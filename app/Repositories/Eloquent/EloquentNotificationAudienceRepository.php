<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\NotificationAudience;
use App\Repositories\Contracts\NotificationAudienceRepositoryInterface;

/**
 * Eloquent implementation of NotificationAudienceRepositoryInterface
 *
 * @extends BaseEloquentRepository<NotificationAudience>
 */
final class EloquentNotificationAudienceRepository extends BaseEloquentRepository implements NotificationAudienceRepositoryInterface
{
    /**
     * Get the model class name
     *
     * @return class-string<NotificationAudience>
     */
    protected function getModelClass(): string
    {
        return NotificationAudience::class;
    }

    /**
     * Create a new notification audience
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): NotificationAudience
    {
        /** @var NotificationAudience $audience */
        $audience = parent::create($data);

        return $audience;
    }
}
