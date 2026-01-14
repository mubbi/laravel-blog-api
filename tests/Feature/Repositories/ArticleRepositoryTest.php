<?php

declare(strict_types=1);

use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ArticleRepository', function () {
    beforeEach(function () {
        $this->repository = app(ArticleRepositoryInterface::class);
    });

    describe('create', function () {
        it('can create an article', function () {
            // Arrange
            $user = \App\Models\User::factory()->create();
            $data = [
                'slug' => 'test-article',
                'title' => 'Test Article',
                'content_markdown' => '# Content',
                'status' => 'draft',
                'created_by' => $user->id,
            ];

            // Act
            $result = $this->repository->create($data);

            // Assert
            expect($result)->toBeInstanceOf(Article::class);
            expect($result->title)->toBe('Test Article');
            expect($result->slug)->toBe('test-article');
            $this->assertDatabaseHas('articles', [
                'title' => 'Test Article',
                'slug' => 'test-article',
            ]);
        });
    });

    describe('findById', function () {
        it('can find article by id', function () {
            // Arrange
            $article = Article::factory()->create();

            // Act
            $result = $this->repository->findById($article->id);

            // Assert
            expect($result)->not->toBeNull();
            expect($result->id)->toBe($article->id);
        });

        it('returns null when article does not exist', function () {
            // Act
            $result = $this->repository->findById(99999);

            // Assert
            expect($result)->toBeNull();
        });
    });

    describe('findOrFail', function () {
        it('can find article by id or fail', function () {
            // Arrange
            $article = Article::factory()->create();

            // Act
            $result = $this->repository->findOrFail($article->id);

            // Assert
            expect($result->id)->toBe($article->id);
        });

        it('throws exception when article does not exist', function () {
            // Act & Assert
            expect(fn () => $this->repository->findOrFail(99999))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });

    describe('findBySlug', function () {
        it('can find article by slug', function () {
            // Arrange
            $article = Article::factory()->create(['slug' => 'test-article']);

            // Act
            $result = $this->repository->findBySlug('test-article');

            // Assert
            expect($result)->not->toBeNull();
            expect($result->slug)->toBe('test-article');
            expect($result->id)->toBe($article->id);
        });

        it('returns null when slug does not exist', function () {
            // Act
            $result = $this->repository->findBySlug('non-existent');

            // Assert
            expect($result)->toBeNull();
        });
    });

    describe('findBySlugOrFail', function () {
        it('can find article by slug or fail', function () {
            // Arrange
            $article = Article::factory()->create(['slug' => 'test-article']);

            // Act
            $result = $this->repository->findBySlugOrFail('test-article');

            // Assert
            expect($result->slug)->toBe('test-article');
            expect($result->id)->toBe($article->id);
        });

        it('throws exception when slug does not exist', function () {
            // Act & Assert
            expect(fn () => $this->repository->findBySlugOrFail('non-existent'))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });

    describe('update', function () {
        it('can update an article', function () {
            // Arrange
            $article = Article::factory()->create(['title' => 'Old Title']);

            // Act
            $result = $this->repository->update($article->id, ['title' => 'New Title']);

            // Assert
            expect($result)->toBeTrue();
            $this->assertDatabaseHas('articles', [
                'id' => $article->id,
                'title' => 'New Title',
            ]);
        });
    });

    describe('delete', function () {
        it('can delete an article', function () {
            // Arrange
            $article = Article::factory()->create();

            // Act
            $result = $this->repository->delete($article->id);

            // Assert
            expect($result)->toBeTrue();
            $this->assertDatabaseMissing('articles', ['id' => $article->id]);
        });
    });

    describe('paginate', function () {
        it('can paginate articles', function () {
            // Arrange
            Article::factory()->count(20)->create();

            // Act
            $result = $this->repository->paginate(['per_page' => 10, 'page' => 1]);

            // Assert
            expect($result->count())->toBe(10);
            expect($result->total())->toBe(20);
        });
    });

    describe('query', function () {
        it('returns query builder instance', function () {
            // Act
            $result = $this->repository->query();

            // Assert
            expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
        });
    });
});
