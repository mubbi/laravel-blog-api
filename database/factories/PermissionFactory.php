<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * PermissionFactory
 *
 * You can override any attribute by passing an array to create/make:
 *   Permission::factory()->create(['name' => 'edit-posts']);
 *
 * Example state usage:
 *   Permission::factory()->editPosts()->create();
 *
 * @see https://laravel.com/docs/12.x/database-testing#factory-states
 */
class PermissionFactory extends Factory
{
    protected $model = \App\Models\Permission::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'slug' => $this->faker->unique()->slug(),
        ];
    }

    /**
     * Indicate that the permission is 'edit-posts'.
     */
    public function editPosts(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'edit-posts',
        ]);
    }
}
