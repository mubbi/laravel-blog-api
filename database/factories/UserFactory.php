<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * UserFactory
 *
 * You can override any attribute by passing an array to create/make:
 *   User::factory()->create(['name' => 'Custom Name']);
 *
 * You can also use states for common scenarios:
 *   User::factory()->admin()->create();
 *
 * @see https://laravel.com/docs/12.x/database-testing#factory-states
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'avatar_url' => fake()->imageUrl(200, 200, 'people'),
            'bio' => fake()->realText(160),
            'twitter' => fake()->userName(),
            'facebook' => fake()->userName(),
            'linkedin' => fake()->userName(),
            'github' => fake()->userName(),
            'website' => fake()->url(),
        ];
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
