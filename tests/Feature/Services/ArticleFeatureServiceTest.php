<?php

declare(strict_types=1);

use App\Events\Article\ArticleFeaturedEvent;
use App\Events\Article\ArticlePinnedEvent;
use App\Events\Article\ArticleUnfeaturedEvent;
use App\Events\Article\ArticleUnpinnedEvent;
use App\Models\Article;
use App\Services\ArticleFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('ArticleFeatureService', function () {
    beforeEach(function () {
        $this->service = app(ArticleFeatureService::class);
    });

    describe('featureArticle', function () {
        it('features an article successfully', function () {
            // Arrange
            Event::fake();
            $article = Article::factory()->create([
                'is_featured' => false,
            ]);

            // Act
            $result = $this->service->featureArticle($article);

            // Assert
            expect($result->is_featured)->toBeTrue();
            expect($result->id)->toBe($article->id);
            Event::assertDispatched(ArticleFeaturedEvent::class);
        });

        it('unfeatures an already featured article', function () {
            // Arrange
            Event::fake();
            $article = Article::factory()->create([
                'is_featured' => true,
            ]);

            // Act
            $result = $this->service->featureArticle($article);

            // Assert
            expect($result->is_featured)->toBeFalse();
            Event::assertDispatched(ArticleUnfeaturedEvent::class);
        });

        it('sets featured_at timestamp when featuring', function () {
            // Arrange
            $article = Article::factory()->create([
                'is_featured' => false,
                'featured_at' => null,
            ]);

            // Act
            $result = $this->service->featureArticle($article);

            // Assert
            expect($result->is_featured)->toBeTrue();
            expect($result->featured_at)->not->toBeNull();
        });

        it('clears featured_at when unfeaturing', function () {
            // Arrange
            $article = Article::factory()->create([
                'is_featured' => true,
                'featured_at' => now(),
            ]);

            // Act
            $result = $this->service->featureArticle($article);

            // Assert
            expect($result->is_featured)->toBeFalse();
            expect($result->featured_at)->toBeNull();
        });
    });

    describe('unfeatureArticle', function () {
        it('unfeatures an article successfully', function () {
            // Arrange
            Event::fake();
            $article = Article::factory()->create([
                'is_featured' => true,
            ]);

            // Act
            $result = $this->service->unfeatureArticle($article->id);

            // Assert
            expect($result->is_featured)->toBeFalse();
            expect($result->featured_at)->toBeNull();
            Event::assertDispatched(ArticleUnfeaturedEvent::class);
        });
    });

    describe('pinArticle', function () {
        it('pins an article successfully', function () {
            // Arrange
            Event::fake();
            $article = Article::factory()->create([
                'is_pinned' => false,
            ]);

            // Act
            $result = $this->service->pinArticle($article);

            // Assert
            expect($result->is_pinned)->toBeTrue();
            expect($result->pinned_at)->not->toBeNull();
            Event::assertDispatched(ArticlePinnedEvent::class);
        });
    });

    describe('unpinArticle', function () {
        it('unpins an article successfully', function () {
            // Arrange
            Event::fake();
            $article = Article::factory()->create([
                'is_pinned' => true,
                'pinned_at' => now(),
            ]);

            // Act
            $result = $this->service->unpinArticle($article);

            // Assert
            expect($result->is_pinned)->toBeFalse();
            expect($result->pinned_at)->toBeNull();
            Event::assertDispatched(ArticleUnpinnedEvent::class);
        });
    });
});
