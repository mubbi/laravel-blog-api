<?php

declare(strict_types=1);

use App\Events\Comment\CommentReportedEvent;
use App\Models\Comment;
use Illuminate\Support\Facades\Event;

describe('API/V1/Comment/ReportCommentController', function () {
    it('can report a comment successfully with reason', function () {
        Event::fake();
        $auth = createAuthenticatedUser();
        $comment = Comment::factory()->create([
            'report_count' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.comments.report', $comment), [
            'reason' => 'Inappropriate content',
        ]);

        expect($response)->toHaveApiSuccessStructure([
            'id',
            'report_count',
            'last_reported_at',
            'report_reason',
        ])->and($response->json('message'))->toBe(__('common.comment_reported_successfully'));

        $comment->refresh();
        expect($comment->report_count)->toBe(1)
            ->and($comment->last_reported_at)->not->toBeNull();

        Event::assertDispatched(CommentReportedEvent::class);
    });

    it('can report a comment without reason', function () {
        Event::fake();
        $auth = createAuthenticatedUser();
        $comment = Comment::factory()->create([
            'report_count' => 2,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.comments.report', $comment));

        expect($response)->toHaveApiSuccessStructure();
        $comment->refresh();
        expect($comment->report_count)->toBe(3);
        Event::assertDispatched(CommentReportedEvent::class);
    });

    it('increments report count on multiple reports', function () {
        Event::fake();
        $auth1 = createAuthenticatedUser();
        $auth2 = createAuthenticatedUser();
        $comment = Comment::factory()->create([
            'report_count' => 0,
        ]);

        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth1['tokenString'],
        ])->postJson(route('api.v1.comments.report', $comment), [
            'reason' => 'First report',
        ]);

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth2['tokenString'],
        ])->postJson(route('api.v1.comments.report', $comment), [
            'reason' => 'Second report',
        ]);

        expect($response1->getStatusCode())->toBe(200)
            ->and($response2->getStatusCode())->toBe(200);

        $comment->refresh();
        expect($comment->report_count)->toBe(2);
    });

    it('returns 422 when reason exceeds max length', function () {
        $auth = createAuthenticatedUser();
        $comment = Comment::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.comments.report', $comment), [
            'reason' => str_repeat('a', 1001),
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The reason field must not be greater than 1000 characters.',
            ]);
    });

    it('returns 401 when not authenticated', function () {
        $comment = Comment::factory()->create();

        $response = $this->postJson(route('api.v1.comments.report', $comment), [
            'reason' => 'Test',
        ]);

        $response->assertStatus(401);
    });

    it('returns 404 when comment does not exist', function () {
        $auth = createAuthenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.comments.report', 99999), [
            'reason' => 'Test',
        ]);

        $response->assertStatus(404);
    });

    it('returns 500 when service throws exception', function () {
        $auth = createAuthenticatedUser();
        $comment = Comment::factory()->create();

        $this->mock(\App\Services\Interfaces\CommentServiceInterface::class, function ($mock) {
            $mock->shouldReceive('reportComment')
                ->andThrow(new \Exception('Service error'));
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.comments.report', $comment), [
            'reason' => 'Test',
        ]);

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });

    it('updates last_reported_at timestamp', function () {
        Event::fake();
        $auth = createAuthenticatedUser();
        $comment = Comment::factory()->create([
            'last_reported_at' => null,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.comments.report', $comment), [
            'reason' => 'Test report',
        ]);

        expect($response->getStatusCode())->toBe(200);
        $comment->refresh();
        expect($comment->last_reported_at)->not->toBeNull();
    });
});
