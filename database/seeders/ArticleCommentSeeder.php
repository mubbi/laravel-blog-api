<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Seeder;

class ArticleCommentSeeder extends Seeder
{
    /**
     * Run this seeder for API testing purpose only.
     * NOTE: DON'T RUN THIS IN PRODUCTION, this is for testing purposes only.
     */
    public function run(): void
    {
        // Create 10 users for comments
        $users = User::factory(10)->create();

        // Create 20 categories and 30 tags
        $categories = \App\Models\Category::factory(20)->create();
        $tags = \App\Models\Tag::factory(30)->create();

        // Create 100 articles
        $articles = Article::factory(100)->create();

        foreach ($articles as $article) {
            // Add 0-3 authors to the authors relation, excluding the main author/created_by
            $possibleAuthors = $users->where('id', '!=', $article->created_by);
            $authorCount = rand(0, 3);
            if ($authorCount > 0 && $possibleAuthors->count() > 0) {
                $authorIds = $possibleAuthors->random(min($authorCount, $possibleAuthors->count()))->pluck('id')->toArray();
                $article->authors()->attach($authorIds);
            }
            // Attach 1-3 random categories to each article
            $article->categories()->attach($categories->random(rand(1, 3))->pluck('id')->toArray());
            // Attach 2-5 random tags to each article
            $article->tags()->attach($tags->random(rand(2, 5))->pluck('id')->toArray());

            // Create 10 top-level comments for each article
            $topComments = [];
            for ($i = 0; $i < 10; $i++) {
                $topComments[$i] = Comment::factory()->create([
                    'article_id' => $article->id,
                    'user_id' => $users->random()->id,
                    'parent_comment_id' => null,
                ]);
            }
            // For each top-level comment, create 2 child comments (level 1)
            foreach ($topComments as $parentComment) {
                for ($j = 0; $j < 2; $j++) {
                    $child = Comment::factory()->create([
                        'article_id' => $article->id,
                        'user_id' => $users->random()->id,
                        'parent_comment_id' => $parentComment->id,
                    ]);
                    // For each child comment, create 2 more child comments (level 2)
                    for ($k = 0; $k < 2; $k++) {
                        Comment::factory()->create([
                            'article_id' => $article->id,
                            'user_id' => $users->random()->id,
                            'parent_comment_id' => $child->id,
                        ]);
                    }
                }
            }
        }
    }
}
