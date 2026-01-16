<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * MediaFactory
 *
 * You can override any attribute by passing an array to create/make:
 *   Media::factory()->create(['name' => 'Custom Media']);
 *
 * @see https://laravel.com/docs/12.x/database-testing#factory-states
 *
 * @extends Factory<Media>
 */
final class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        $fileName = Str::random(20).'.jpg';
        $path = 'media/'.date('Y/m').'/'.$fileName;

        return [
            'name' => $this->faker->sentence(3),
            'file_name' => $fileName,
            'mime_type' => 'image/jpeg',
            'disk' => 'public',
            'path' => $path,
            'url' => '/storage/'.$path,
            'size' => $this->faker->numberBetween(1000, 10000000), // 1KB to 10MB
            'type' => 'image',
            'alt_text' => $this->faker->optional()->sentence,
            'caption' => $this->faker->optional()->sentence,
            'description' => $this->faker->optional()->text(500),
            'metadata' => [
                'width' => 800,
                'height' => 600,
                'dimensions' => '800x600',
            ],
            'uploaded_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the media is an image.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'file_name' => Str::random(20).'.jpg',
        ]);
    }

    /**
     * Indicate that the media is a video.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'video',
            'mime_type' => 'video/mp4',
            'file_name' => Str::random(20).'.mp4',
        ]);
    }

    /**
     * Indicate that the media is a document.
     */
    public function document(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'document',
            'mime_type' => 'application/pdf',
            'file_name' => Str::random(20).'.pdf',
        ]);
    }
}
