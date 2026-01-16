<?php

declare(strict_types=1);

use App\Enums\CommentStatus;
use App\Events\Comment\CommentCreatedEvent;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\Services\Interfaces\CommentServiceInterface;
use Illuminate\Support\Facades\Event;

describe('API/V1/Comment/CreateCommentController', function () {
    it('can create a comment successfully', function () {
        Event::fake();
        $auth = createAuthenticatedUser();
        $article = Article::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.comments.store', $article), [
            'content' => 'This is a test comment',
        ]);

        expect($response)->toHaveApiSuccessStructure([
            'id',
            'content',
            'status',
            'status_display',
            'is_approved',
            'created_at',
            'updated_at',
        ])->and($response->json('data.content'))->toBe('This is a test comment')
            ->and($response->json('data.status'))->toBe(CommentStatus::PENDING->value);

        $this->assertDatabaseHas('comments', [
            'content' => 'This is a test comment',
            'user_id' => $auth['user']->id,
            'article_id' => $article->id,
            'status' => CommentStatus::PENDING->value,
        ]);

        Event::assertDispatched(CommentCreatedEvent::class);
    });

    it('can create a reply comment successfully', function () {
        Event::fake();
        $auth = createAuthenticatedUser();
        $article = Article::factory()->create();
        $parentComment = Comment::factory()->create([
            'article_id' => $article->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.comments.store', $article), [
            'content' => 'This is a reply',
            'parent_comment_id' => $parentComment->id,
        ]);

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.content'))->toBe('This is a reply')
            ->and($response->json('data.parent_comment_id'))->toBe($parentComment->id);

        $this->assertDatabaseHas('comments', [
            'content' => 'This is a reply',
            'parent_comment_id' => $parentComment->id,
            'article_id' => $article->id,
        ]);

        Event::assertDispatched(CommentCreatedEvent::class);
    });

    it('returns 422 when content is missing', function () {
        $auth = createAuthenticatedUser();
        $article = Article::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.comments.store', $article), []);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The content field is required.',
            ]);
    });

    it('returns 422 when content exceeds max length', function () {
        $auth = createAuthenticatedUser();
        $article = Article::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.comments.store', $article), [
            'content' => str_repeat('a', 5001),
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The content field must not be greater than 5000 characters.',
            ]);
    });

    it('returns 422 when parent_comment_id does not exist', function () {
        $auth = createAuthenticatedUser();
        $article = Article::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.comments.store', $article), [
            'content' => 'Test comment',
            'parent_comment_id' => 99999,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The selected parent comment id is invalid.',
            ]);
    });

    it('returns 500 when parent comment belongs to different article', function () {
        $auth = createAuthenticatedUser();
        $article1 = Article::factory()->create();
        $article2 = Article::factory()->create();
        $parentComment = Comment::factory()->create([
            'article_id' => $article1->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.comments.store', $article2), [
            'content' => 'Test comment',
            'parent_comment_id' => $parentComment->id,
        ]);

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });

    it('returns 401 when not authenticated', function () {
        $article = Article::factory()->create();

        $response = $this->postJson(route('api.v1.comments.store', $article), [
            'content' => 'Test comment',
        ]);

        $response->assertStatus(401);
    });

    it('returns 500 when service throws exception', function () {
        $auth = createAuthenticatedUser();
        $article = Article::factory()->create();

        $this->mock(CommentServiceInterface::class, function ($mock) {
            $mock->shouldReceive('createComment')
                ->andThrow(new \Exception('Service error'));
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.comments.store', $article), [
            'content' => 'Test comment',
        ]);

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });

    it('creates comment with minimum content length', function () {
        Event::fake();
        $auth = createAuthenticatedUser();
        $article = Article::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.comments.store', $article), [
            'content' => 'A',
        ]);

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.content'))->toBe('A');
    });

    it('creates comment with maximum content length', function () {
        Event::fake();
        $auth = createAuthenticatedUser();
        $article = Article::factory()->create();
        $maxContent = str_repeat('a', 5000);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.comments.store', $article), [
            'content' => $maxContent,
        ]);

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.content'))->toBe($maxContent);
    });
});
