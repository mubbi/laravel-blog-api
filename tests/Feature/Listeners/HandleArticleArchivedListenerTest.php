<?php

declare(strict_types=1);

use App\Events\Article\ArticleArchivedEvent;
use App\Listeners\Article\HandleArticleArchivedListener;
use App\Models\Article;
use Illuminate\Support\Facades\Log;

describe('HandleArticleArchivedListener', function () {
    it('handles ArticleArchivedEvent and logs information', function () {
        // Arrange
        Log::spy();
        $article = Article::factory()->create([
            'title' => 'Test Article',
        ]);
        $event = new ArticleArchivedEvent($article);
        $listener = new HandleArticleArchivedListener;

        // Act
        $listener->handle($event);

        // Assert
        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) use ($article) {
            return $message === __('log.article_archived')
                && $context['article_id'] === $article->id
                && $context['title'] === $article->title;
        })->once();
    });

    it('implements ShouldQueue interface', function () {
        // Assert
        expect(new HandleArticleArchivedListener)
            ->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
    });
});
