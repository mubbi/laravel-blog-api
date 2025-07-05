<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationType;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * NotificationFactory
 *
 * You can override any attribute by passing an array to create/make:
 *   Notification::factory()->create(['type' => 'newsletter']);
 *
 * Example state usage:
 *   Notification::factory()->newsletter()->create();
 *
 * @see https://laravel.com/docs/12.x/database-testing#factory-states
 */
/**
 * @extends Factory<Notification>
 */
final class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(NotificationType::cases())->value,
            'message' => ['message' => $this->faker->sentence()],
        ];
    }

    /**
     * Indicate that the notification is a newsletter.
     */
    public function newsletter(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => NotificationType::NEWSLETTER->value,
        ]);
    }
}
