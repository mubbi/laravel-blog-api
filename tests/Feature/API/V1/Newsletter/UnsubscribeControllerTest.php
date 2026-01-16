<?php

declare(strict_types=1);

use App\Events\Newsletter\NewsletterSubscriberUnsubscriptionRequestedEvent;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

describe('API/V1/Newsletter/UnsubscribeController', function () {
    it('sends unsubscription token to valid email', function () {
        // Arrange
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'unsubscribe@example.com',
            'is_verified' => true,
            'unsubscribed_at' => null,
        ]);

        Mail::fake();
        Event::fake([NewsletterSubscriberUnsubscriptionRequestedEvent::class]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.unsubscribe'), [
            'email' => 'unsubscribe@example.com',
        ]);

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('message'))->toBe(__('newsletter.unsubscription_token_sent'))
            ->and($response->json('data'))->toBeNull();

        // Verify token was generated
        $subscriber->refresh();
        expect($subscriber->verification_token)->not->toBeNull();
        expect($subscriber->verification_token_expires_at)->not->toBeNull();
        // Subscriber should not be unsubscribed yet
        expect($subscriber->unsubscribed_at)->toBeNull();

        Event::assertDispatched(NewsletterSubscriberUnsubscriptionRequestedEvent::class, function ($event) use ($subscriber) {
            return $event->subscriberId === $subscriber->id
                && $event->email === 'unsubscribe@example.com';
        });
    });

    it('returns validation error for unverified subscriber', function () {
        // Arrange
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'unverified@example.com',
            'is_verified' => false,
            'verification_token' => Hash::make('some-token'),
            'unsubscribed_at' => null,
        ]);

        Mail::fake();
        Event::fake([NewsletterSubscriberUnsubscriptionRequestedEvent::class]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.unsubscribe'), [
            'email' => 'unverified@example.com',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'error' => [
                    'email' => [__('newsletter.subscriber_not_verified')],
                ],
            ]);

        // Verify token was not changed (should still match the original token)
        $subscriber->refresh();
        expect(Hash::check('some-token', $subscriber->verification_token))->toBeTrue();

        Event::assertNotDispatched(NewsletterSubscriberUnsubscriptionRequestedEvent::class);
    });

    it('returns validation error if already unsubscribed', function () {
        // Arrange
        $unsubscribedAt = now()->subDays(5);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'already-unsubscribed@example.com',
            'is_verified' => true,
            'unsubscribed_at' => $unsubscribedAt,
            'verification_token' => null,
        ]);

        Mail::fake();
        Event::fake([NewsletterSubscriberUnsubscriptionRequestedEvent::class]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.unsubscribe'), [
            'email' => 'already-unsubscribed@example.com',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'error' => [
                    'email' => [__('newsletter.subscriber_already_unsubscribed')],
                ],
            ]);

        // Verify token was not generated
        $subscriber->refresh();
        expect($subscriber->verification_token)->toBeNull();
        // Unsubscribed_at should remain unchanged
        expect($subscriber->unsubscribed_at->format('Y-m-d H:i:s'))
            ->toBe($unsubscribedAt->format('Y-m-d H:i:s'));

        Event::assertNotDispatched(NewsletterSubscriberUnsubscriptionRequestedEvent::class);
    });

    it('validates email is required', function () {
        // Act
        $response = $this->postJson(route('api.v1.newsletter.unsubscribe'), []);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The email field is required.',
                'data' => null,
                'error' => [
                    'email' => ['The email field is required.'],
                ],
            ]);
    });

    it('validates email format', function () {
        // Act
        $response = $this->postJson(route('api.v1.newsletter.unsubscribe'), [
            'email' => 'invalid-email',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'error' => [
                    'email' => ['The email field must be a valid email address.'],
                ],
            ]);
    });

    it('validates email max length', function () {
        // Act
        $response = $this->postJson(route('api.v1.newsletter.unsubscribe'), [
            'email' => str_repeat('a', 250).'@example.com',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'error' => [
                    'email' => ['The email field must not be greater than 255 characters.'],
                ],
            ]);
    });

    it('returns 404 when subscriber does not exist', function () {
        // Act
        $response = $this->postJson(route('api.v1.newsletter.unsubscribe'), [
            'email' => 'nonexistent@example.com',
        ]);

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => __('common.subscriber_not_found'),
                'data' => null,
                'error' => null,
            ]);
    });

    it('dispatches NewsletterSubscriberUnsubscriptionRequestedEvent when requesting unsubscription', function () {
        // Arrange
        Event::fake([NewsletterSubscriberUnsubscriptionRequestedEvent::class]);

        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'event-unsubscribe@example.com',
            'is_verified' => true,
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.unsubscribe'), [
            'email' => 'event-unsubscribe@example.com',
        ]);

        // Assert
        $response->assertStatus(200);

        Event::assertDispatched(NewsletterSubscriberUnsubscriptionRequestedEvent::class, function ($event) use ($subscriber) {
            return $event->subscriberId === $subscriber->id
                && $event->email === 'event-unsubscribe@example.com';
        });
    });

    it('handles service exception and returns 500', function () {
        // Arrange
        $this->mock(\App\Services\Interfaces\NewsletterServiceInterface::class, function ($mock) {
            $mock->shouldReceive('unsubscribe')
                ->andThrow(new \Exception('Database error'));
        });

        // Act
        $response = $this->postJson(route('api.v1.newsletter.unsubscribe'), [
            'email' => 'error@example.com',
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

    it('handles ModelNotFoundException and returns 404', function () {
        // Arrange
        $this->mock(\App\Services\Interfaces\NewsletterServiceInterface::class, function ($mock) {
            $exception = new ModelNotFoundException;
            $exception->setModel(\App\Models\NewsletterSubscriber::class);
            $mock->shouldReceive('unsubscribe')
                ->andThrow($exception);
        });

        // Act
        $response = $this->postJson(route('api.v1.newsletter.unsubscribe'), [
            'email' => 'notfound@example.com',
        ]);

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => __('common.subscriber_not_found'),
                'data' => null,
                'error' => null,
            ]);
    });

    it('does not require authentication', function () {
        // Arrange
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'public-unsubscribe@example.com',
            'is_verified' => true,
            'unsubscribed_at' => null,
        ]);

        Mail::fake();

        // Act
        $response = $this->postJson(route('api.v1.newsletter.unsubscribe'), [
            'email' => 'public-unsubscribe@example.com',
        ]);

        // Assert
        $response->assertStatus(200);
    });

    it('generates token with expiration when requesting unsubscription', function () {
        // Arrange
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'token-expiration@example.com',
            'is_verified' => true,
        ]);

        $beforeRequest = now();

        // Act
        $response = $this->postJson(route('api.v1.newsletter.unsubscribe'), [
            'email' => 'token-expiration@example.com',
        ]);

        // Assert
        $response->assertStatus(200);

        $subscriber->refresh();
        expect($subscriber->verification_token)->not->toBeNull();
        expect($subscriber->verification_token_expires_at)->not->toBeNull();
        // Token expiration should be in the future
        expect($subscriber->verification_token_expires_at->timestamp)
            ->toBeGreaterThan($beforeRequest->timestamp);
    });

    it('can request unsubscription for subscriber with user relationship', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => $user->email,
            'user_id' => $user->id,
            'is_verified' => true,
        ]);

        Mail::fake();

        // Act
        $response = $this->postJson(route('api.v1.newsletter.unsubscribe'), [
            'email' => $user->email,
        ]);

        // Assert
        $response->assertStatus(200);

        $subscriber->refresh();
        expect($subscriber->verification_token)->not->toBeNull();
        expect($subscriber->user_id)->toBe($user->id); // User relationship preserved
        expect($subscriber->unsubscribed_at)->toBeNull(); // Not unsubscribed yet
    });

    it('can request unsubscription for subscriber without user relationship', function () {
        // Arrange
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'guest@example.com',
            'user_id' => null,
            'is_verified' => true,
        ]);

        Mail::fake();

        // Act
        $response = $this->postJson(route('api.v1.newsletter.unsubscribe'), [
            'email' => 'guest@example.com',
        ]);

        // Assert
        $response->assertStatus(200);

        $subscriber->refresh();
        expect($subscriber->verification_token)->not->toBeNull();
        expect($subscriber->user_id)->toBeNull();
        expect($subscriber->unsubscribed_at)->toBeNull(); // Not unsubscribed yet
    });
});
