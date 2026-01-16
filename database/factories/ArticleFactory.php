<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * ArticleFactory
 *
 * You can override any attribute by passing an array to create/make:
 *   Article::factory()->create(['title' => 'Custom Title']);
 *
 * You can also use states for common scenarios:
 *   Article::factory()->published()->create();
 *
 * @see https://laravel.com/docs/12.x/database-testing#factory-states
 */
/**
 * @extends Factory<Article>
 */
final class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug,
            'title' => $this->faker->sentence,
            'subtitle' => $this->faker->optional()->sentence,
            'excerpt' => $this->faker->optional()->text(200),
            'content_markdown' => $this->faker->paragraphs(3, true),
            'content_html' => $this->faker->optional()->randomHtml(),
            'featured_media_id' => null, // Can be overridden in tests when needed
            'status' => $this->faker->randomElement(ArticleStatus::cases())->value,
            'published_at' => $this->faker->optional()->dateTimeThisYear(),
            'meta_title' => $this->faker->optional()->sentence,
            'meta_description' => $this->faker->optional()->text(200),
            'created_by' => User::factory(),
            'approved_by' => null, // Will be set based on status
            'updated_by' => null,
            'is_featured' => false,
            'is_pinned' => false,
            'featured_at' => null,
            'pinned_at' => null,
            'report_count' => 0,
            'last_reported_at' => null,
            'report_reason' => null,
        ];
    }

    /**
     * Indicate that the article is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::PUBLISHED->value,
            'published_at' => now(),
            'approved_by' => User::factory(),
        ]);
    }

    /**
     * Indicate that the article is draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::DRAFT->value,
            'published_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Indicate that the article is under review.
     */
    public function review(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::REVIEW->value,
            'published_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Indicate that the article is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::SCHEDULED->value,
            'published_at' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
            'approved_by' => User::factory(),
        ]);
    }

    /**
     * Indicate that the article is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::ARCHIVED->value,
            'published_at' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
            'approved_by' => User::factory(),
        ]);
    }

    /**
     * Indicate that the article is trashed.
     */
    public function trashed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::TRASHED->value,
            'published_at' => null,
            'approved_by' => null,
        ]);
    }
}
