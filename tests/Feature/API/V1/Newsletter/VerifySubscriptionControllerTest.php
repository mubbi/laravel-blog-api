<?php

declare(strict_types=1);

use App\Events\Newsletter\NewsletterSubscriberVerifiedEvent;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

describe('API/V1/Newsletter/VerifySubscriptionController', function () {
    it('can verify subscription with valid token', function () {
        // Arrange
        $token = Str::random(64);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'verify@example.com',
            'is_verified' => false,
            'verification_token' => Hash::make($token),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
            'token' => $token,
            'email' => 'verify@example.com',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'email',
                    'is_verified',
                    'subscribed_at',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'status' => true,
                'message' => __('common.subscriber_verified_successfully'),
                'data' => [
                    'email' => 'verify@example.com',
                    'is_verified' => true,
                ],
            ]);

        // Verify subscriber was verified
        $subscriber->refresh();
        expect($subscriber->is_verified)->toBeTrue();
        expect($subscriber->verification_token)->toBeNull();
    });

    it('returns existing verified subscriber if already verified with same token', function () {
        // Arrange
        $token = Str::random(64);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'already-verified@example.com',
            'is_verified' => true,
            'verification_token' => null, // Verified subscribers have null token
        ]);

        // Create an unverified subscriber with the token
        $unverifiedSubscriber = NewsletterSubscriber::factory()->create([
            'email' => 'unverified@example.com',
            'is_verified' => false,
            'verification_token' => Hash::make($token),
        ]);

        // Act - Verify the unverified subscriber
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
            'token' => $token,
            'email' => 'unverified@example.com',
        ]);

        // Assert - Should verify successfully
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'email' => 'unverified@example.com',
                    'is_verified' => true,
                ],
            ]);

        $unverifiedSubscriber->refresh();
        expect($unverifiedSubscriber->is_verified)->toBeTrue();
    });

    it('validates token is required', function () {
        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
            'email' => 'test@example.com',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The token field is required.',
                'data' => null,
                'error' => [
                    'token' => ['The token field is required.'],
                ],
            ]);
    });

    it('validates email is required', function () {
        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
            'token' => Str::random(64),
        ]);

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

    it('validates token size is 64 characters', function () {
        // Act - Token too short
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
            'token' => Str::random(63),
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

        // Act - Token too long
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
            'token' => Str::random(65),
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

    it('returns 404 when token does not exist', function () {
        // Arrange
        $nonExistentToken = Str::random(64);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
            'token' => $nonExistentToken,
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

    it('handles verification when subscriber is already verified', function () {
        // Arrange
        $token = Str::random(64);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'pre-verified@example.com',
            'is_verified' => true,
            'verification_token' => null, // Already verified, token cleared
        ]);

        // Create another subscriber with the token (simulating token reuse scenario)
        $otherSubscriber = NewsletterSubscriber::factory()->create([
            'email' => 'other@example.com',
            'is_verified' => false,
            'verification_token' => Hash::make($token),
        ]);

        // Act - Verify the unverified subscriber
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
            'token' => $token,
            'email' => 'other@example.com',
        ]);

        // Assert - Should verify the unverified subscriber, not the already verified one
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'email' => 'other@example.com',
                    'is_verified' => true,
                ],
            ]);
    });

    it('dispatches NewsletterSubscriberVerifiedEvent when verifying', function () {
        // Arrange
        Event::fake([NewsletterSubscriberVerifiedEvent::class]);

        $token = Str::random(64);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'event-verify@example.com',
            'is_verified' => false,
            'verification_token' => Hash::make($token),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
            'token' => $token,
            'email' => 'event-verify@example.com',
        ]);

        // Assert
        $response->assertStatus(200);

        Event::assertDispatched(NewsletterSubscriberVerifiedEvent::class, function ($event) use ($subscriber) {
            return $event->subscriberId === $subscriber->id
                && $event->email === 'event-verify@example.com';
        });
    });

    it('does not dispatch event if already verified', function () {
        // Arrange
        Event::fake([NewsletterSubscriberVerifiedEvent::class]);

        // Create an already verified subscriber (no token)
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'already-verified@example.com',
            'is_verified' => true,
            'verification_token' => null,
        ]);

        // Try to verify with a non-existent token
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
            'token' => Str::random(64),
            'email' => 'already-verified@example.com',
        ]);

        // Assert
        $response->assertStatus(404);

        // Event should not be dispatched
        Event::assertNotDispatched(NewsletterSubscriberVerifiedEvent::class);
    });

    it('handles service exception and returns 500', function () {
        // Arrange
        $this->mock(\App\Services\Interfaces\NewsletterServiceInterface::class, function ($mock) {
            $mock->shouldReceive('verifySubscription')
                ->andThrow(new \Exception('Database error'));
        });

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
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
            $mock->shouldReceive('verifySubscription')
                ->andThrow($exception);
        });

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
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
        NewsletterSubscriber::factory()->create([
            'email' => 'public-verify@example.com',
            'is_verified' => false,
            'verification_token' => Hash::make($token),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
            'token' => $token,
            'email' => 'public-verify@example.com',
        ]);

        // Assert
        $response->assertStatus(200);
    });

    it('clears verification_token after successful verification', function () {
        // Arrange
        $token = Str::random(64);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'clear-token@example.com',
            'is_verified' => false,
            'verification_token' => Hash::make($token),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
            'token' => $token,
            'email' => 'clear-token@example.com',
        ]);

        // Assert
        $response->assertStatus(200);

        $subscriber->refresh();
        expect($subscriber->verification_token)->toBeNull();
        expect($subscriber->is_verified)->toBeTrue();
    });

    it('can verify subscriber with user relationship', function () {
        // Arrange
        $user = User::factory()->create();
        $token = Str::random(64);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => $user->email,
            'user_id' => $user->id,
            'is_verified' => false,
            'verification_token' => Hash::make($token),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
            'token' => $token,
            'email' => $user->email,
        ]);

        // Assert
        $response->assertStatus(200);

        $subscriber->refresh();
        expect($subscriber->is_verified)->toBeTrue();
        expect($subscriber->user_id)->toBe($user->id); // User relationship preserved
    });

    it('can verify subscriber without user relationship', function () {
        // Arrange
        $token = Str::random(64);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'guest-verify@example.com',
            'user_id' => null,
            'is_verified' => false,
            'verification_token' => Hash::make($token),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
            'token' => $token,
            'email' => 'guest-verify@example.com',
        ]);

        // Assert
        $response->assertStatus(200);

        $subscriber->refresh();
        expect($subscriber->is_verified)->toBeTrue();
        expect($subscriber->user_id)->toBeNull();
    });

    it('validates token is a string', function () {
        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
            'token' => 12345,
            'email' => 'test@example.com',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'error' => [
                    'token' => ['The token field must be a string.'],
                ],
            ]);
    });

    it('returns 404 when token is expired', function () {
        // Arrange
        $token = Str::random(64);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'expired@example.com',
            'is_verified' => false,
            'verification_token' => Hash::make($token),
            'verification_token_expires_at' => now()->subHour(), // Expired 1 hour ago
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
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

    it('can verify subscription with valid non-expired token', function () {
        // Arrange
        $token = Str::random(64);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'valid-token@example.com',
            'is_verified' => false,
            'verification_token' => Hash::make($token),
            'verification_token_expires_at' => now()->addHours(24), // Valid for 24 hours
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
            'token' => $token,
            'email' => 'valid-token@example.com',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'email' => 'valid-token@example.com',
                    'is_verified' => true,
                ],
            ]);

        // Verify token and expiration are cleared
        $subscriber->refresh();
        expect($subscriber->is_verified)->toBeTrue();
        expect($subscriber->verification_token)->toBeNull();
        expect($subscriber->verification_token_expires_at)->toBeNull();
    });

    it('clears verification_token_expires_at after successful verification', function () {
        // Arrange
        $token = Str::random(64);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'clear-expiration@example.com',
            'is_verified' => false,
            'verification_token' => Hash::make($token),
            'verification_token_expires_at' => now()->addHours(24),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
            'token' => $token,
            'email' => 'clear-expiration@example.com',
        ]);

        // Assert
        $response->assertStatus(200);

        $subscriber->refresh();
        expect($subscriber->verification_token_expires_at)->toBeNull();
    });

    it('returns 404 when token does not match email', function () {
        // Arrange
        $token = Str::random(64);
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'correct@example.com',
            'is_verified' => false,
            'verification_token' => Hash::make($token),
        ]);

        // Act - Try to verify with wrong email
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
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

    it('validates email format', function () {
        // Act
        $response = $this->postJson(route('api.v1.newsletter.verify'), [
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
});
