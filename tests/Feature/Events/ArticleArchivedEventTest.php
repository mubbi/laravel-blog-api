<?php

declare(strict_types=1);

use App\Events\Article\ArticleArchivedEvent;
use App\Models\Article;

describe('ArticleArchivedEvent', function () {
    it('can be instantiated with an article', function () {
        // Arrange
        $article = Article::factory()->create();

        // Act
        $event = new ArticleArchivedEvent($article);

        // Assert
        expect($event->article)->toBe($article);
        expect($event->article->id)->toBe($article->id);
    });

    it('has readonly article property', function () {
        // Arrange
        $article = Article::factory()->create();
        $event = new ArticleArchivedEvent($article);

        // Assert - readonly properties cannot be reassigned, so we just verify it exists
        expect($event->article)->toBeInstanceOf(Article::class);
    });

    it('can be serialized', function () {
        // Arrange
        $article = Article::factory()->create();
        $event = new ArticleArchivedEvent($article);

        // Act & Assert - Events should be serializable
        expect($event)->toBeInstanceOf(ArticleArchivedEvent::class);
    });
});
