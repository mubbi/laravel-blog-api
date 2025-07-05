<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Notification;
use App\Models\NotificationAudience;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * NotificationAudienceFactory
 *
 * You can override any attribute by passing an array to create/make:
 *   NotificationAudience::factory()->create(['user_id' => 1]);
 *
 * @see https://laravel.com/docs/12.x/database-testing#factory-states
 *
 * @extends Factory<NotificationAudience>
 */
final class NotificationAudienceFactory extends Factory
{
    protected $model = NotificationAudience::class;

    public function definition(): array
    {
        return [
            'notification_id' => Notification::factory(),
            'user_id' => User::factory(),
        ];
    }
}
