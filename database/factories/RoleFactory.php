<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * RoleFactory
 *
 * You can override any attribute by passing an array to create/make:
 *   Role::factory()->create(['name' => 'admin']);
 *
 * Example state usage:
 *   Role::factory()->admin()->create();
 *
 * @see https://laravel.com/docs/12.x/database-testing#factory-states
 */
class RoleFactory extends Factory
{
    protected $model = \App\Models\Role::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
        ];
    }

    /**
     * Indicate that the role is admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'admin',
        ]);
    }
}
