<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CommentStatus;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * CommentFactory
 *
 * You can override any attribute by passing an array to create/make:
 *   Comment::factory()->create(['content' => 'My comment']);
 *
 * Example state usage:
 *   Comment::factory()->reply()->create();
 *
 * @see https://laravel.com/docs/12.x/database-testing#factory-states
 *
 * @extends Factory<Comment>
 */
final class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'user_id' => User::factory(),
            'content' => $this->faker->paragraph,
            'parent_comment_id' => null,
            'status' => CommentStatus::PENDING->value,
            'approved_at' => null,
            'approved_by' => null,
            'report_count' => 0,
            'last_reported_at' => null,
            'report_reason' => null,
            'moderator_notes' => null,
            'admin_note' => null,
            'deleted_reason' => null,
            'deleted_by' => null,
            'deleted_at' => null,
        ];
    }

    /**
     * Indicate that the comment is a reply (has a parent_comment_id).
     */
    public function reply(int $parentId = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_comment_id' => $parentId,
        ]);
    }

    /**
     * Indicate that the comment is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CommentStatus::APPROVED->value,
            'approved_at' => now(),
            'approved_by' => User::factory(),
        ]);
    }

    /**
     * Indicate that the comment is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CommentStatus::REJECTED->value,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Indicate that the comment is spam.
     */
    public function spam(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CommentStatus::SPAM->value,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }
}
