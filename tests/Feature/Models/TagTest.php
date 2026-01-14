<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Tag Model', function () {
    it('can be created', function () {
        // Act
        $tag = Tag::factory()->create([
            'name' => 'PHP',
            'slug' => 'php',
        ]);

        // Assert
        expect($tag->name)->toBe('PHP');
        expect($tag->slug)->toBe('php');
        expect($tag->id)->toBeInt();
    });

    it('has articles relationship', function () {
        // Arrange
        $tag = Tag::factory()->create();
        $article1 = Article::factory()->create();
        $article2 = Article::factory()->create();

        $tag->articles()->attach([$article1->id, $article2->id]);

        // Act
        $articles = $tag->articles;

        // Assert
        expect($articles)->toHaveCount(2);
        expect($articles->pluck('id')->toArray())->toContain($article1->id, $article2->id);
    });

    it('can attach articles to tag', function () {
        // Arrange
        $tag = Tag::factory()->create();
        $article = Article::factory()->create();

        // Act
        $tag->articles()->attach($article->id);

        // Assert
        expect($tag->articles)->toHaveCount(1);
        expect($tag->articles->first()->id)->toBe($article->id);
    });

    it('can detach articles from tag', function () {
        // Arrange
        $tag = Tag::factory()->create();
        $article = Article::factory()->create();
        $tag->articles()->attach($article->id);

        // Act
        $tag->articles()->detach($article->id);

        // Assert
        expect($tag->fresh()->articles)->toHaveCount(0);
    });

    it('can sync articles to tag', function () {
        // Arrange
        $tag = Tag::factory()->create();
        $article1 = Article::factory()->create();
        $article2 = Article::factory()->create();
        $article3 = Article::factory()->create();
        $tag->articles()->attach([$article1->id, $article2->id]);

        // Act
        $tag->articles()->sync([$article2->id, $article3->id]);

        // Assert
        expect($tag->fresh()->articles)->toHaveCount(2);
        expect($tag->articles->pluck('id')->toArray())->toContain($article2->id, $article3->id);
        expect($tag->articles->pluck('id')->toArray())->not->toContain($article1->id);
    });

    it('has timestamps', function () {
        // Arrange
        $tag = Tag::factory()->create();

        // Assert
        expect($tag->created_at)->not->toBeNull();
        expect($tag->updated_at)->not->toBeNull();
    });
});
