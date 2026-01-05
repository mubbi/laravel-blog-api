<?php

declare(strict_types=1);

use App\Events\Comment\CommentReportedEvent;
use App\Models\Comment;
use App\Models\User;
use App\Services\CommentService;
use Illuminate\Support\Facades\Event;

describe('API/V1/Comment/ReportCommentController', function () {
    it('can report a comment successfully with reason', function () {
        // Arrange
        Event::fake();
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $comment = Comment::factory()->create([
            'report_count' => 0,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.comments.report', $comment), [
            'reason' => 'Inappropriate content',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'report_count',
                    'last_reported_at',
                    'report_reason',
                ],
            ])
            ->assertJson([
                'status' => true,
                'message' => __('common.comment_reported_successfully'),
            ]);

        $comment->refresh();
        expect($comment->report_count)->toBe(1);
        expect($comment->last_reported_at)->not->toBeNull();

        Event::assertDispatched(CommentReportedEvent::class);
    });

    it('can report a comment without reason', function () {
        // Arrange
        Event::fake();
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $comment = Comment::factory()->create([
            'report_count' => 2,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.comments.report', $comment));

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
            ]);

        $comment->refresh();
        expect($comment->report_count)->toBe(3);

        Event::assertDispatched(CommentReportedEvent::class);
    });

    it('increments report count on multiple reports', function () {
        // Arrange
        Event::fake();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token1 = $user1->createToken('test-token', ['access-api']);
        $token2 = $user2->createToken('test-token', ['access-api']);
        $comment = Comment::factory()->create([
            'report_count' => 0,
        ]);

        // Act
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer '.$token1->plainTextToken,
        ])->postJson(route('api.v1.comments.report', $comment), [
            'reason' => 'First report',
        ]);

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer '.$token2->plainTextToken,
        ])->postJson(route('api.v1.comments.report', $comment), [
            'reason' => 'Second report',
        ]);

        // Assert
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $comment->refresh();
        expect($comment->report_count)->toBe(2);
    });

    it('returns 422 when reason exceeds max length', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $comment = Comment::factory()->create();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.comments.report', $comment), [
            'reason' => str_repeat('a', 1001),
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The reason field must not be greater than 1000 characters.',
            ]);
    });

    it('returns 401 when not authenticated', function () {
        // Arrange
        $comment = Comment::factory()->create();

        // Act
        $response = $this->postJson(route('api.v1.comments.report', $comment), [
            'reason' => 'Test',
        ]);

        // Assert
        $response->assertStatus(401);
    });

    it('returns 404 when comment does not exist', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.comments.report', 99999), [
            'reason' => 'Test',
        ]);

        // Assert
        $response->assertStatus(404);
    });

    it('returns 500 when service throws exception', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $comment = Comment::factory()->create();

        // Mock CommentService to throw exception
        $this->mock(CommentService::class, function ($mock) {
            $mock->shouldReceive('reportComment')
                ->andThrow(new \Exception('Service error'));
        });

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.comments.report', $comment), [
            'reason' => 'Test',
        ]);

        // Assert
        $response->assertStatus(500)
            ->assertJson([
                'status' => false,
                'message' => __('common.something_went_wrong'),
                'data' => null,
                'error' => null,
            ]);
    });

    it('updates last_reported_at timestamp', function () {
        // Arrange
        Event::fake();
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $comment = Comment::factory()->create([
            'last_reported_at' => null,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.comments.report', $comment), [
            'reason' => 'Test report',
        ]);

        // Assert
        $response->assertStatus(200);

        $comment->refresh();
        expect($comment->last_reported_at)->not->toBeNull();
    });
});
