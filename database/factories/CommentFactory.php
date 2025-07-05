<?php

declare(strict_types=1);

namespace Database\Factories;

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
}
