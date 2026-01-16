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
        Cache::flush();
    });

    it('can get articles with basic pagination', function () {
        $user = User::factory()->create();
        Article::factory()
            ->count(25)
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        $response = $this->getJson(route('api.v1.articles.index'));

        expect($response)->toHaveApiSuccessStructure([
            'articles' => [
                '*' => [
                    'id',
                    'slug',
                    'title',
                    'subtitle',
                    'excerpt',
                    'featured_media',
                    'status',
                    'published_at',
                    'created_at',
                    'updated_at',
                    'author' => ['id', 'name', 'avatar_url'],
                    'categories',
                    'tags',
                    'comments_count',
                ],
            ],
            'meta',
        ])->and($response->json('data.articles'))->toHaveCount(15);
    });

    it('can filter articles by category', function () {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $article = createPublishedArticle($user, $user);
        $article->categories()->attach($category->id);
        createPublishedArticle($user, $user);

        $response = $this->getJson(route('api.v1.articles.index', ['category_slug' => $category->slug]));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.articles'))->toHaveCount(1)
            ->and($response->json('data.articles.0.id'))->toBe($article->id);
    });

    it('can filter articles by tag', function () {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();
        $article = createPublishedArticle($user, $user);
        $article->tags()->attach($tag->id);
        createPublishedArticle($user, $user);

        $response = $this->getJson(route('api.v1.articles.index', ['tag_slug' => $tag->slug]));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.articles'))->toHaveCount(1)
            ->and($response->json('data.articles.0.id'))->toBe($article->id);
    });

    it('can search articles', function () {
        $user = User::factory()->create();
        $article = createPublishedArticle($user, $user, [
            'title' => 'Laravel Testing Guide',
            'subtitle' => 'Learn Laravel testing',
            'excerpt' => 'This guide covers Laravel testing best practices',
            'content_markdown' => 'This comprehensive guide covers Laravel testing best practices and patterns for modern web development.',
        ]);
        createPublishedArticle($user, $user, ['title' => 'PHP Best Practices']);

        // Optimize table to force full-text index update (InnoDB updates indexes asynchronously)
        \Illuminate\Support\Facades\DB::statement('OPTIMIZE TABLE articles');

        $response = $this->getJson(route('api.v1.articles.index', ['search' => 'Laravel']));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.articles'))->toHaveCount(1)
            ->and($response->json('data.articles.0.id'))->toBe($article->id);
    });

    it('can filter articles by author', function () {
        $author1 = User::factory()->create();
        $author2 = User::factory()->create();
        $article1 = createPublishedArticle($author1, $author1);
        createPublishedArticle($author2, $author2);

        $response = $this->getJson(route('api.v1.articles.index', ['created_by' => $author1->id]));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.articles'))->toHaveCount(1)
            ->and($response->json('data.articles.0.id'))->toBe($article1->id);
    });

    it('can filter articles by status', function () {
        $user = User::factory()->create();
        $publishedArticle = createPublishedArticle($user, $user);
        createDraftArticle($user);

        $response = $this->getJson(route('api.v1.articles.index', ['status' => 'published']));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.articles'))->toHaveCount(1)
            ->and($response->json('data.articles.0.id'))->toBe($publishedArticle->id);
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

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.articles'))->toHaveCount(5)
            ->and($response->json('data.meta.current_page'))->toBe(2)
            ->and($response->json('data.meta.per_page'))->toBe(5);
    });

    it('can sort articles', function () {
        $user = User::factory()->create();
        $article1 = createPublishedArticle($user, $user, ['title' => 'A Article']);
        $article2 = createPublishedArticle($user, $user, ['title' => 'Z Article']);

        $response = $this->getJson(route('api.v1.articles.index', ['sort_by' => 'title', 'sort_direction' => 'asc']));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.articles.0.id'))->toBe($article1->id)
            ->and($response->json('data.articles.1.id'))->toBe($article2->id);
    });

    it('returns 500 when getting articles fails with exception', function () {
        $this->mock(ArticleServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getArticles')
                ->andThrow(new \Exception('Database connection failed'));
        });

        $response = $this->getJson(route('api.v1.articles.index'));

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });
});
