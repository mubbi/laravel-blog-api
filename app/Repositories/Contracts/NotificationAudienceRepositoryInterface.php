<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\NotificationAudience;

/**
 * NotificationAudience repository interface
 */
interface NotificationAudienceRepositoryInterface
{
    /**
     * Create a new notification audience
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): NotificationAudience;
}
