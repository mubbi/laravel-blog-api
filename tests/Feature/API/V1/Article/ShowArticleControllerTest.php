<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Tag;
use App\Models\User;

describe('API/V1/Article/ShowArticleController', function () {
    it('can get single article by slug', function () {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $article = createPublishedArticle($user, $user);
        $article->categories()->attach($category->id);
        $article->tags()->attach($tag->id);

        $response = $this->getJson(route('api.v1.articles.show', ['slug' => $article->slug]));

        expect($response)->toHaveApiSuccessStructure([
            'id',
            'slug',
            'title',
            'subtitle',
            'excerpt',
            'content_html',
            'content_markdown',
            'featured_media',
            'status',
            'published_at',
            'meta_title',
            'meta_description',
            'created_at',
            'updated_at',
            'author' => ['id', 'name', 'avatar_url', 'bio'],
            'categories' => ['*' => ['id', 'name', 'slug']],
            'tags' => ['*' => ['id', 'name', 'slug']],
            'authors',
            'comments_count',
        ])->and($response->json('data.id'))->toBe($article->id)
            ->and($response->json('data.slug'))->toBe($article->slug);
    });

    it('returns 404 when article not found by slug', function () {
        $response = $this->getJson(route('api.v1.articles.show', ['slug' => 'non-existent-slug']));

        expect($response->getStatusCode())->toBe(404)
            ->and($response->json('status'))->toBeFalse()
            ->and($response->json('message'))->toBe(__('common.article_not_found'))
            ->and($response->json('data'))->toBeNull();
    });

    it('returns 500 when showing article fails with exception', function () {
        $this->mock(\App\Services\Interfaces\ArticleServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getArticleBySlug')
                ->with('test-slug')
                ->andThrow(new \Exception('Database connection failed'));
        });

        $response = $this->getJson(route('api.v1.articles.show', ['slug' => 'test-slug']));

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });
});
