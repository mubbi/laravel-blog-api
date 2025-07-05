<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * TagFactory
 *
 * You can override any attribute by passing an array to create/make:
 *   Tag::factory()->create(['name' => 'Custom Tag']);
 *
 * @see https://laravel.com/docs/12.x/database-testing#factory-states
 *
 * @extends Factory<Tag>
 */
final class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word,
            'slug' => $this->faker->unique()->slug,
        ];
    }
}
