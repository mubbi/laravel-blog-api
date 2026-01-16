<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Tag;

describe('Tag Model', function () {
    it('can be created', function () {
        $tag = Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);

        expect($tag->name)->toBe('PHP')
            ->and($tag->slug)->toBe('php')
            ->and($tag->id)->toBeInt();
    });

    it('has articles relationship', function () {
        $tag = Tag::factory()->create();
        $article1 = Article::factory()->create();
        $article2 = Article::factory()->create();
        $tag->articles()->attach([$article1->id, $article2->id]);

        $articles = $tag->articles;

        expect($articles)->toHaveCount(2)
            ->and($articles->pluck('id')->toArray())->toContain($article1->id, $article2->id);
    });

    it('can attach articles to tag', function () {
        $tag = Tag::factory()->create();
        $article = Article::factory()->create();

        $tag->articles()->attach($article->id);

        expect($tag->articles)->toHaveCount(1)
            ->and($tag->articles->first()->id)->toBe($article->id);
    });

    it('can detach articles from tag', function () {
        $tag = Tag::factory()->create();
        $article = Article::factory()->create();
        $tag->articles()->attach($article->id);

        $tag->articles()->detach($article->id);

        expect($tag->fresh()->articles)->toHaveCount(0);
    });

    it('can sync articles to tag', function () {
        $tag = Tag::factory()->create();
        $article1 = Article::factory()->create();
        $article2 = Article::factory()->create();
        $article3 = Article::factory()->create();
        $tag->articles()->attach([$article1->id, $article2->id]);

        $tag->articles()->sync([$article2->id, $article3->id]);

        expect($tag->fresh()->articles)->toHaveCount(2)
            ->and($tag->articles->pluck('id')->toArray())->toContain($article2->id, $article3->id)
            ->and($tag->articles->pluck('id')->toArray())->not->toContain($article1->id);
    });

    it('has timestamps', function () {
        $tag = Tag::factory()->create();

        expect($tag->created_at)->not->toBeNull()
            ->and($tag->updated_at)->not->toBeNull();
    });
});
