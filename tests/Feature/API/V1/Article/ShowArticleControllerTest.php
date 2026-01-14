<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;

describe('API/V1/Article/ShowArticleController', function () {
    it('can get single article by slug', function () {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $article = Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        $article->categories()->attach($category->id);
        $article->tags()->attach($tag->id);

        $response = $this->getJson(route('api.v1.articles.show', ['slug' => $article->slug]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'slug',
                    'title',
                    'subtitle',
                    'excerpt',
                    'content_html',
                    'content_markdown',
                    'featured_image',
                    'status',
                    'published_at',
                    'meta_title',
                    'meta_description',
                    'created_at',
                    'updated_at',
                    'author' => [
                        'id',
                        'name',
                        'avatar_url',
                        'bio',
                    ],
                    'categories' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                        ],
                    ],
                    'tags' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                        ],
                    ],
                    'authors',
                    'comments_count',
                ],
            ]);

        expect($response->json('data.id'))->toBe($article->id);
        expect($response->json('data.slug'))->toBe($article->slug);
    });

    it('returns 404 when article not found by slug', function () {
        $response = $this->getJson(route('api.v1.articles.show', ['slug' => 'non-existent-slug']));
        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => __('common.article_not_found'),
                'data' => null,
                'error' => null,
            ]);
    });

    it('returns 500 when showing article fails with exception', function () {
        // Mock ArticleService to throw an exception
        $this->mock(\App\Services\Interfaces\ArticleServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getArticleBySlug')
                ->with('test-slug')
                ->andThrow(new \Exception('Database connection failed'));
        });

        $response = $this->getJson(route('api.v1.articles.show', ['slug' => 'test-slug']));

        $response->assertStatus(500)
            ->assertJson([
                'status' => false,
                'message' => __('common.something_went_wrong'),
                'data' => null,
                'error' => null,
            ]);
    });
});
