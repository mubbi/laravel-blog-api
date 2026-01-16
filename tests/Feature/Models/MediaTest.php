<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Media Model', function () {
    describe('relationships', function () {
        it('belongs to uploader', function () {
            $user = User::factory()->create();
            $media = Media::factory()->for($user, 'uploader')->create();

            $uploader = $media->uploader;

            expect($uploader)->toBeInstanceOf(User::class)
                ->and($uploader->id)->toBe($user->id);
        });

        it('has many featured articles', function () {
            $media = Media::factory()->create();
            Article::factory()->count(3)->create(['featured_media_id' => $media->id]);

            $featuredArticles = $media->featuredInArticles;

            expect($featuredArticles)->toHaveCount(3)
                ->and($featuredArticles->first())->toBeInstanceOf(Article::class);
        });

        it('belongs to many articles via pivot', function () {
            $media = Media::factory()->create();
            $article1 = Article::factory()->create();
            $article2 = Article::factory()->create();

            $media->articles()->attach($article1->id, ['usage_type' => 'content', 'order' => 1]);
            $media->articles()->attach($article2->id, ['usage_type' => 'gallery', 'order' => 2]);

            $articles = $media->articles;

            expect($articles)->toHaveCount(2)
                ->and($articles->first()->pivot->usage_type)->toBe('content')
                ->and($articles->last()->pivot->usage_type)->toBe('gallery');
        });
    });

    describe('casts', function () {
        it('casts metadata to array', function () {
            $metadata = ['width' => 800, 'height' => 600];
            $media = Media::factory()->create(['metadata' => $metadata]);

            $result = Media::find($media->id);

            expect($result->metadata)->toBeArray()
                ->and($result->metadata['width'])->toBe(800);
        });

        it('casts size to integer', function () {
            $media = Media::factory()->create(['size' => '1024']);

            $result = Media::find($media->id);

            expect($result->size)->toBeInt()
                ->and($result->size)->toBe(1024);
        });
    });

    describe('fillable attributes', function () {
        it('allows mass assignment of fillable attributes', function () {
            $user = User::factory()->create();
            $data = [
                'name' => 'Test Media',
                'file_name' => 'test.jpg',
                'mime_type' => 'image/jpeg',
                'disk' => 'public',
                'path' => 'media/test.jpg',
                'url' => '/storage/media/test.jpg',
                'size' => 1024,
                'type' => 'image',
                'alt_text' => 'Test alt',
                'caption' => 'Test caption',
                'description' => 'Test description',
                'metadata' => ['test' => 'data'],
            ];

            $media = Media::factory()->for($user, 'uploader')->create($data);

            expect($media->name)->toBe('Test Media')
                ->and($media->type)->toBe('image');
        });
    });

    describe('guarded attributes', function () {
        it('guards id from mass assignment', function () {
            $user = User::factory()->create();
            $media = Media::factory()->for($user, 'uploader')->create();
            $originalId = $media->id;

            // Try to update with id - should throw MassAssignmentException
            expect(fn () => $media->update(['id' => 99999]))
                ->toThrow(\Illuminate\Database\Eloquent\MassAssignmentException::class);

            // Verify the id didn't change
            expect($media->fresh()->id)->toBe($originalId);
        });

        it('guards uploaded_by from mass assignment', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $media = Media::factory()->for($user1, 'uploader')->create();

            // Try to update with uploaded_by - should throw MassAssignmentException
            expect(fn () => $media->update(['uploaded_by' => $user2->id]))
                ->toThrow(\Illuminate\Database\Eloquent\MassAssignmentException::class);

            // Verify the uploaded_by didn't change
            expect($media->fresh()->uploaded_by)->toBe($user1->id);
        });
    });
});
