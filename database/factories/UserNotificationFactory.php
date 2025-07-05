<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * UserNotificationFactory
 *
 * You can override any attribute by passing an array to create/make:
 *   UserNotification::factory()->create(['is_read' => true]);
 *
 * Example state usage:
 *   UserNotification::factory()->read()->create();
 *
 * @see https://laravel.com/docs/12.x/eloquent-factories#factory-states
 *
 * @extends Factory<UserNotification>
 */
final class UserNotificationFactory extends Factory
{
    protected $model = UserNotification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'notification_id' => Notification::factory(),
            'is_read' => $this->faker->boolean,
        ];
    }

    /**
     * Indicate that the notification is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
        ]);
    }

    /**
     * Indicate that the notification is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
        ]);
    }
}
