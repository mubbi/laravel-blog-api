<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * NewsletterSubscriberFactory
 *
 * You can override any attribute by passing an array to create/make:
 *   NewsletterSubscriber::factory()->create(['is_verified' => true]);
 *
 * Example state usage:
 *   NewsletterSubscriber::factory()->verified()->create();
 *
 * @see https://laravel.com/docs/12.x/database-testing#factory-states
 *
 * @extends Factory<NewsletterSubscriber>
 */
final class NewsletterSubscriberFactory extends Factory
{
    protected $model = NewsletterSubscriber::class;

    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'user_id' => User::factory(),
            'is_verified' => $this->faker->boolean,
            'subscribed_at' => $this->faker->dateTimeThisYear(),
        ];
    }

    /**
     * Indicate that the subscriber is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }
}
