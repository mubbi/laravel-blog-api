<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Services\Interfaces\ArticleServiceInterface;
use Illuminate\Support\Facades\Cache;

describe('API/V1/Article/GetArticlesController', function () {
    beforeEach(function () {
        // Clear all caches before each test for isolation
        Cache::flush();
    });
    it('can get articles with basic pagination', function () {
        // Create test data
        $user = User::factory()->create();
        $articles = Article::factory()
            ->count(25)
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        $response = $this->getJson(route('api.v1.articles.index'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'articles' => [
                        '*' => [
                            'id',
                            'slug',
                            'title',
                            'subtitle',
                            'excerpt',
                            'featured_image',
                            'status',
                            'published_at',
                            'created_at',
                            'updated_at',
                            'author' => [
                                'id',
                                'name',
                                'avatar_url',
                            ],
                            'categories',
                            'tags',
                            'comments_count',
                        ],
                    ],
                    'meta',
                ],
            ]);

        // Should return 15 articles per page by default
        expect($response->json('data.articles'))->toHaveCount(15);
    });

    it('can filter articles by category', function () {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create article with category
        $article = Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        $article->categories()->attach($category->id);

        // Create article without category
        Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        $response = $this->getJson(route('api.v1.articles.index', ['category_slug' => $category->slug]));

        $response->assertStatus(200);
        expect($response->json('data.articles'))->toHaveCount(1);
        expect($response->json('data.articles.0.id'))->toBe($article->id);
    });

    it('can filter articles by tag', function () {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        // Create article with tag
        $article = Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        $article->tags()->attach($tag->id);

        // Create article without tag
        Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        $response = $this->getJson(route('api.v1.articles.index', ['tag_slug' => $tag->slug]));

        $response->assertStatus(200);
        expect($response->json('data.articles'))->toHaveCount(1);
        expect($response->json('data.articles.0.id'))->toBe($article->id);
    });

    it('can search articles', function () {
        $user = User::factory()->create();

        $article = Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create(['title' => 'Laravel Testing Guide']);

        Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create(['title' => 'PHP Best Practices']);

        $response = $this->getJson(route('api.v1.articles.index', ['search' => 'Laravel']));

        $response->assertStatus(200);
        expect($response->json('data.articles'))->toHaveCount(1);
        expect($response->json('data.articles.0.id'))->toBe($article->id);
    });

    it('can filter articles by author', function () {
        $author1 = User::factory()->create();
        $author2 = User::factory()->create();

        $article1 = Article::factory()
            ->for($author1, 'author')
            ->for($author1, 'approver')
            ->published()
            ->create();

        Article::factory()
            ->for($author2, 'author')
            ->for($author2, 'approver')
            ->published()
            ->create();

        $response = $this->getJson(route('api.v1.articles.index', ['created_by' => $author1->id]));

        $response->assertStatus(200);
        expect($response->json('data.articles'))->toHaveCount(1);
        expect($response->json('data.articles.0.id'))->toBe($article1->id);
    });

    it('can filter articles by status', function () {
        $user = User::factory()->create();

        $publishedArticle = Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->draft()
            ->create();

        $response = $this->getJson(route('api.v1.articles.index', ['status' => 'published']));

        $response->assertStatus(200);
        expect($response->json('data.articles'))->toHaveCount(1);
        expect($response->json('data.articles.0.id'))->toBe($publishedArticle->id);
    });

    it('can customize pagination', function () {
        $user = User::factory()->create();
        Article::factory()
            ->count(30)
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        $response = $this->getJson(route('api.v1.articles.index', ['per_page' => 5, 'page' => 2]));

        $response->assertStatus(200);
        expect($response->json('data.articles'))->toHaveCount(5);
        expect($response->json('data.meta.current_page'))->toBe(2);
        expect($response->json('data.meta.per_page'))->toBe(5);
    });

    it('can sort articles', function () {
        $user = User::factory()->create();

        $article1 = Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create(['title' => 'A Article']);

        $article2 = Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create(['title' => 'Z Article']);

        $response = $this->getJson(route('api.v1.articles.index', ['sort_by' => 'title', 'sort_direction' => 'asc']));

        $response->assertStatus(200);
        expect($response->json('data.articles.0.id'))->toBe($article1->id);
        expect($response->json('data.articles.1.id'))->toBe($article2->id);
    });

    it('returns 500 when getting articles fails with exception', function () {
        // Mock ArticleServiceInterface to throw an exception
        $this->mock(ArticleServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getArticles')
                ->andThrow(new \Exception('Database connection failed'));
        });

        $response = $this->getJson(route('api.v1.articles.index'));

        $response->assertStatus(500)
            ->assertJson([
                'status' => false,
                'message' => __('common.something_went_wrong'),
                'data' => null,
                'error' => null,
            ]);
    });
});
