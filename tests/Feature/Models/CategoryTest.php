<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Category Model', function () {
    it('can be created', function () {
        $category = Category::factory()->create(['name' => 'Technology', 'slug' => 'technology']);

        expect($category->name)->toBe('Technology')
            ->and($category->slug)->toBe('technology')
            ->and($category->id)->toBeInt();
    });

    it('has articles relationship', function () {
        $category = Category::factory()->create();
        $article1 = Article::factory()->create();
        $article2 = Article::factory()->create();
        $category->articles()->attach([$article1->id, $article2->id]);

        $articles = $category->articles;

        expect($articles)->toHaveCount(2)
            ->and($articles->pluck('id')->toArray())->toContain($article1->id, $article2->id);
    });

    it('can attach articles to category', function () {
        $category = Category::factory()->create();
        $article = Article::factory()->create();

        $category->articles()->attach($article->id);

        expect($category->articles)->toHaveCount(1)
            ->and($category->articles->first()->id)->toBe($article->id);
    });

    it('can detach articles from category', function () {
        $category = Category::factory()->create();
        $article = Article::factory()->create();
        $category->articles()->attach($article->id);

        $category->articles()->detach($article->id);

        expect($category->fresh()->articles)->toHaveCount(0);
    });

    it('can sync articles to category', function () {
        $category = Category::factory()->create();
        $article1 = Article::factory()->create();
        $article2 = Article::factory()->create();
        $article3 = Article::factory()->create();
        $category->articles()->attach([$article1->id, $article2->id]);

        $category->articles()->sync([$article2->id, $article3->id]);

        expect($category->fresh()->articles)->toHaveCount(2)
            ->and($category->articles->pluck('id')->toArray())->toContain($article2->id, $article3->id)
            ->and($category->articles->pluck('id')->toArray())->not->toContain($article1->id);
    });

    it('has timestamps', function () {
        $category = Category::factory()->create();

        expect($category->created_at)->not->toBeNull()
            ->and($category->updated_at)->not->toBeNull();
    });
});
