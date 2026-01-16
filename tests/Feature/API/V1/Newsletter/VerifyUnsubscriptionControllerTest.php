<?php

declare(strict_types=1);

use App\Events\Newsletter\NewsletterSubscriberUnsubscribedEvent;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

describe('API/V1/Newsletter/VerifyUnsubscriptionController', function () {
    it('can verify unsubscription with valid token', function () {
        // Arrange
        $token = Str::random(64);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'verify-unsubscribe@example.com',
            'is_verified' => true,
            'verification_token' => Hash::make($token),
            'verification_token_expires_at' => now()->addHours(24),
            'unsubscribed_at' => null,
        ]);

        Event::fake([NewsletterSubscriberUnsubscribedEvent::class]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify-unsubscribe'), [
            'token' => $token,
            'email' => 'verify-unsubscribe@example.com',
        ]);

        expect($response)->toHaveApiSuccessStructure([
            'id',
            'email',
            'is_verified',
            'subscribed_at',
            'unsubscribed_at',
            'created_at',
            'updated_at',
        ])->and($response->json('message'))->toBe(__('common.subscriber_unsubscribed_successfully'))
            ->and($response->json('data.email'))->toBe('verify-unsubscribe@example.com')
            ->and($response->json('data.is_verified'))->toBeFalse();

        // Verify subscriber was unsubscribed
        $subscriber->refresh();
        expect($subscriber->unsubscribed_at)->not->toBeNull();
        expect($subscriber->is_verified)->toBeFalse();
        expect($subscriber->verification_token)->toBeNull();
        expect($subscriber->verification_token_expires_at)->toBeNull();

        Event::assertDispatched(NewsletterSubscriberUnsubscribedEvent::class, function ($event) use ($subscriber) {
            return $event->subscriberId === $subscriber->id
                && $event->email === 'verify-unsubscribe@example.com';
        });
    });

    it('returns existing unsubscribed subscriber if already unsubscribed', function () {
        // Arrange
        $token = Str::random(64);
        $unsubscribedAt = now()->subDays(5);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'already-unsubscribed@example.com',
            'is_verified' => false,
            'verification_token' => Hash::make($token),
            'verification_token_expires_at' => now()->addHours(24),
            'unsubscribed_at' => $unsubscribedAt,
        ]);

        Event::fake([NewsletterSubscriberUnsubscribedEvent::class]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify-unsubscribe'), [
            'token' => $token,
            'email' => 'already-unsubscribed@example.com',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $subscriber->id,
                    'email' => 'already-unsubscribed@example.com',
                ],
            ]);

        // Verify unsubscribed_at timestamp was not changed
        $subscriber->refresh();
        expect($subscriber->unsubscribed_at->format('Y-m-d H:i:s'))
            ->toBe($unsubscribedAt->format('Y-m-d H:i:s'));
        // Token should be cleared
        expect($subscriber->verification_token)->toBeNull();

        // Event should not be dispatched again
        Event::assertNotDispatched(NewsletterSubscriberUnsubscribedEvent::class);
    });

    it('validates token is required', function () {
        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify-unsubscribe'), [
            'email' => 'test@example.com',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The token field is required.',
                'error' => [
                    'token' => ['The token field is required.'],
                ],
            ]);
    });

    it('validates token size', function () {
        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify-unsubscribe'), [
            'token' => 'short-token',
            'email' => 'test@example.com',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'error' => [
                    'token' => ['The token field must be 64 characters.'],
                ],
            ]);
    });

    it('validates email is required', function () {
        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify-unsubscribe'), [
            'token' => Str::random(64),
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The email field is required.',
                'error' => [
                    'email' => ['The email field is required.'],
                ],
            ]);
    });

    it('validates email format', function () {
        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify-unsubscribe'), [
            'token' => Str::random(64),
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

    it('returns 404 when token is invalid', function () {
        // Arrange
        NewsletterSubscriber::factory()->create([
            'email' => 'test@example.com',
            'verification_token' => Hash::make(Str::random(64)),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify-unsubscribe'), [
            'token' => Str::random(64),
            'email' => 'test@example.com',
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

    it('returns 404 when email does not match', function () {
        // Arrange
        $token = Str::random(64);
        NewsletterSubscriber::factory()->create([
            'email' => 'correct@example.com',
            'verification_token' => Hash::make($token),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify-unsubscribe'), [
            'token' => $token,
            'email' => 'wrong@example.com',
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

    it('returns 404 when token is expired', function () {
        // Arrange
        $token = Str::random(64);
        NewsletterSubscriber::factory()->create([
            'email' => 'expired@example.com',
            'verification_token' => Hash::make($token),
            'verification_token_expires_at' => now()->subHours(1),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify-unsubscribe'), [
            'token' => $token,
            'email' => 'expired@example.com',
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

    it('dispatches NewsletterSubscriberUnsubscribedEvent when verifying unsubscription', function () {
        // Arrange
        Event::fake([NewsletterSubscriberUnsubscribedEvent::class]);

        $token = Str::random(64);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'event-unsubscribe@example.com',
            'is_verified' => true,
            'verification_token' => Hash::make($token),
            'verification_token_expires_at' => now()->addHours(24),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify-unsubscribe'), [
            'token' => $token,
            'email' => 'event-unsubscribe@example.com',
        ]);

        // Assert
        $response->assertStatus(200);

        Event::assertDispatched(NewsletterSubscriberUnsubscribedEvent::class, function ($event) use ($subscriber) {
            return $event->subscriberId === $subscriber->id
                && $event->email === 'event-unsubscribe@example.com';
        });
    });

    it('does not dispatch event if already unsubscribed', function () {
        // Arrange
        Event::fake([NewsletterSubscriberUnsubscribedEvent::class]);

        $token = Str::random(64);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'already-unsub@example.com',
            'unsubscribed_at' => now()->subDays(5),
            'verification_token' => Hash::make($token),
            'verification_token_expires_at' => now()->addHours(24),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify-unsubscribe'), [
            'token' => $token,
            'email' => 'already-unsub@example.com',
        ]);

        // Assert
        $response->assertStatus(200);

        // Event should not be dispatched again
        Event::assertNotDispatched(NewsletterSubscriberUnsubscribedEvent::class);
    });

    it('handles service exception and returns 500', function () {
        // Arrange
        $this->mock(\App\Services\Interfaces\NewsletterServiceInterface::class, function ($mock) {
            $mock->shouldReceive('verifyUnsubscription')
                ->andThrow(new \Exception('Database error'));
        });

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify-unsubscribe'), [
            'token' => Str::random(64),
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
            $mock->shouldReceive('verifyUnsubscription')
                ->andThrow($exception);
        });

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify-unsubscribe'), [
            'token' => Str::random(64),
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
        $token = Str::random(64);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'public-unsubscribe@example.com',
            'verification_token' => Hash::make($token),
            'verification_token_expires_at' => now()->addHours(24),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify-unsubscribe'), [
            'token' => $token,
            'email' => 'public-unsubscribe@example.com',
        ]);

        // Assert
        $response->assertStatus(200);
    });

    it('sets unsubscribed_at timestamp correctly', function () {
        // Arrange
        $token = Str::random(64);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'timestamp@example.com',
            'is_verified' => true,
            'verification_token' => Hash::make($token),
            'verification_token_expires_at' => now()->addHours(24),
        ]);

        $beforeUnsubscribe = now();

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify-unsubscribe'), [
            'token' => $token,
            'email' => 'timestamp@example.com',
        ]);

        // Assert
        $response->assertStatus(200);

        $subscriber->refresh();
        expect($subscriber->unsubscribed_at)->not->toBeNull();
        // Allow for microsecond differences by checking the timestamp is not before unsubscribe time
        expect($subscriber->unsubscribed_at->timestamp)->toBeGreaterThanOrEqual($beforeUnsubscribe->timestamp);
    });

    it('can verify unsubscription for subscriber with user relationship', function () {
        // Arrange
        $user = User::factory()->create();
        $token = Str::random(64);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => $user->email,
            'user_id' => $user->id,
            'is_verified' => true,
            'verification_token' => Hash::make($token),
            'verification_token_expires_at' => now()->addHours(24),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify-unsubscribe'), [
            'token' => $token,
            'email' => $user->email,
        ]);

        // Assert
        $response->assertStatus(200);

        $subscriber->refresh();
        expect($subscriber->unsubscribed_at)->not->toBeNull();
        expect($subscriber->user_id)->toBe($user->id); // User relationship preserved
    });

    it('can verify unsubscription for subscriber without user relationship', function () {
        // Arrange
        $token = Str::random(64);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'guest@example.com',
            'user_id' => null,
            'is_verified' => true,
            'verification_token' => Hash::make($token),
            'verification_token_expires_at' => now()->addHours(24),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify-unsubscribe'), [
            'token' => $token,
            'email' => 'guest@example.com',
        ]);

        // Assert
        $response->assertStatus(200);

        $subscriber->refresh();
        expect($subscriber->unsubscribed_at)->not->toBeNull();
        expect($subscriber->user_id)->toBeNull();
    });

    it('clears verification_token_expires_at when verifying unsubscription', function () {
        // Arrange
        $token = Str::random(64);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'clear-expiration@example.com',
            'is_verified' => true,
            'verification_token' => Hash::make($token),
            'verification_token_expires_at' => now()->addHours(24),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify-unsubscribe'), [
            'token' => $token,
            'email' => 'clear-expiration@example.com',
        ]);

        // Assert
        $response->assertStatus(200);

        $subscriber->refresh();
        expect($subscriber->verification_token_expires_at)->toBeNull();
        expect($subscriber->verification_token)->toBeNull();
    });
});
