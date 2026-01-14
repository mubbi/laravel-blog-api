<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Category Model', function () {
    it('can be created', function () {
        // Act
        $category = Category::factory()->create([
            'name' => 'Technology',
            'slug' => 'technology',
        ]);

        // Assert
        expect($category->name)->toBe('Technology');
        expect($category->slug)->toBe('technology');
        expect($category->id)->toBeInt();
    });

    it('has articles relationship', function () {
        // Arrange
        $category = Category::factory()->create();
        $article1 = Article::factory()->create();
        $article2 = Article::factory()->create();

        $category->articles()->attach([$article1->id, $article2->id]);

        // Act
        $articles = $category->articles;

        // Assert
        expect($articles)->toHaveCount(2);
        expect($articles->pluck('id')->toArray())->toContain($article1->id, $article2->id);
    });

    it('can attach articles to category', function () {
        // Arrange
        $category = Category::factory()->create();
        $article = Article::factory()->create();

        // Act
        $category->articles()->attach($article->id);

        // Assert
        expect($category->articles)->toHaveCount(1);
        expect($category->articles->first()->id)->toBe($article->id);
    });

    it('can detach articles from category', function () {
        // Arrange
        $category = Category::factory()->create();
        $article = Article::factory()->create();
        $category->articles()->attach($article->id);

        // Act
        $category->articles()->detach($article->id);

        // Assert
        expect($category->fresh()->articles)->toHaveCount(0);
    });

    it('can sync articles to category', function () {
        // Arrange
        $category = Category::factory()->create();
        $article1 = Article::factory()->create();
        $article2 = Article::factory()->create();
        $article3 = Article::factory()->create();
        $category->articles()->attach([$article1->id, $article2->id]);

        // Act
        $category->articles()->sync([$article2->id, $article3->id]);

        // Assert
        expect($category->fresh()->articles)->toHaveCount(2);
        expect($category->articles->pluck('id')->toArray())->toContain($article2->id, $article3->id);
        expect($category->articles->pluck('id')->toArray())->not->toContain($article1->id);
    });

    it('has timestamps', function () {
        // Arrange
        $category = Category::factory()->create();

        // Assert
        expect($category->created_at)->not->toBeNull();
        expect($category->updated_at)->not->toBeNull();
    });
});
