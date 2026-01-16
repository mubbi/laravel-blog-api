<?php

declare(strict_types=1);

use App\Events\Newsletter\NewsletterSubscriberCreatedEvent;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

describe('API/V1/Newsletter/SubscribeController', function () {
    it('can subscribe to newsletter with valid email', function () {
        // Arrange
        $email = 'test@example.com';

        // Act
        $response = $this->postJson(route('api.v1.newsletter.subscribe'), [
            'email' => $email,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
            ])
            ->assertJson([
                'status' => true,
                'message' => __('newsletter.verification_token_sent'),
                'data' => null,
            ]);

        // Verify subscriber was created in database
        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => $email,
            'is_verified' => false,
        ]);

        $subscriber = NewsletterSubscriber::where('email', $email)->first();
        expect($subscriber)->not->toBeNull();
        expect($subscriber->verification_token)->not->toBeNull();
        expect($subscriber->unsubscribed_at)->toBeNull();
        // Verify token is hashed (bcrypt hashes are typically 60 characters)
        expect(strlen($subscriber->verification_token))->toBeGreaterThanOrEqual(60);
    });

    it('can subscribe authenticated user and links user_id', function () {
        // Arrange
        $user = User::factory()->create();
        $email = $user->email;

        // Act
        $response = $this->actingAs($user)
            ->postJson(route('api.v1.newsletter.subscribe'), [
                'email' => $email,
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
            ])
            ->assertJson([
                'status' => true,
                'message' => __('newsletter.verification_token_sent'),
                'data' => null,
            ]);

        // Verify subscriber was created with user_id
        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => $email,
            'user_id' => $user->id,
        ]);
    });

    it('regenerates token for already verified and subscribed user', function () {
        // Arrange
        $oldToken = Hash::make('old-token');
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'verified@example.com',
            'is_verified' => true,
            'unsubscribed_at' => null,
            'verification_token' => $oldToken,
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.subscribe'), [
            'email' => 'verified@example.com',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
            ])
            ->assertJson([
                'status' => true,
                'message' => __('newsletter.verification_token_sent'),
                'data' => null,
            ]);

        // Verify no duplicate was created
        expect(NewsletterSubscriber::where('email', 'verified@example.com')->count())->toBe(1);

        // Verify token was regenerated and subscriber is now unverified
        $subscriber->refresh();
        expect($subscriber->verification_token)->not->toBe($oldToken);
        expect($subscriber->is_verified)->toBeFalse();
        // Token should not be in response
        expect($response->json('data'))->toBeNull();
    });

    it('regenerates token for unverified existing subscriber', function () {
        // Arrange
        $oldToken = Hash::make('old-token');
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'unverified@example.com',
            'is_verified' => false,
            'verification_token' => $oldToken,
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.subscribe'), [
            'email' => 'unverified@example.com',
        ]);

        // Assert
        $response->assertStatus(200);

        $subscriber->refresh();
        expect($subscriber->verification_token)->not->toBe($oldToken);
        expect($subscriber->verification_token)->not->toBeNull();
    });

    it('resubscribes previously unsubscribed user', function () {
        // Arrange
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'unsubscribed@example.com',
            'is_verified' => false,
            'unsubscribed_at' => now()->subDays(5),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.subscribe'), [
            'email' => 'unsubscribed@example.com',
        ]);

        // Assert
        $response->assertStatus(200);

        $subscriber->refresh();
        expect($subscriber->unsubscribed_at)->toBeNull();
        expect($subscriber->verification_token)->not->toBeNull();
        expect($subscriber->is_verified)->toBeFalse();
    });

    it('validates email is required', function () {
        // Act
        $response = $this->postJson(route('api.v1.newsletter.subscribe'), []);

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
        $response = $this->postJson(route('api.v1.newsletter.subscribe'), [
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
        $response = $this->postJson(route('api.v1.newsletter.subscribe'), [
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

    it('dispatches NewsletterSubscriberCreatedEvent when subscribing', function () {
        // Arrange
        Event::fake([NewsletterSubscriberCreatedEvent::class]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.subscribe'), [
            'email' => 'event@example.com',
        ]);

        // Assert
        $response->assertStatus(200);

        Event::assertDispatched(NewsletterSubscriberCreatedEvent::class, function ($event) {
            return $event->email === 'event@example.com'
                && ! empty($event->verificationToken);
        });
    });

    it('dispatches event for resubscription', function () {
        // Arrange
        Event::fake([NewsletterSubscriberCreatedEvent::class]);

        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'resubscribe@example.com',
            'unsubscribed_at' => now()->subDays(5),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.subscribe'), [
            'email' => 'resubscribe@example.com',
        ]);

        // Assert
        $response->assertStatus(200);

        Event::assertDispatched(NewsletterSubscriberCreatedEvent::class, function ($event) use ($subscriber) {
            return $event->subscriberId === $subscriber->id
                && $event->email === 'resubscribe@example.com';
        });
    });

    it('handles service exception and returns 500', function () {
        $this->mock(\App\Services\Interfaces\NewsletterServiceInterface::class, function ($mock) {
            $mock->shouldReceive('subscribe')
                ->andThrow(new \Exception('Database error'));
        });

        $response = $this->postJson(route('api.v1.newsletter.subscribe'), [
            'email' => 'error@example.com',
        ]);

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });

    it('does not require authentication', function () {
        // Act
        $response = $this->postJson(route('api.v1.newsletter.subscribe'), [
            'email' => 'public@example.com',
        ]);

        // Assert
        $response->assertStatus(200);
    });

    it('creates subscriber with correct subscribed_at timestamp', function () {
        // Arrange
        $email = 'timestamp@example.com';
        $beforeSubscribe = now();

        // Act
        $response = $this->postJson(route('api.v1.newsletter.subscribe'), [
            'email' => $email,
        ]);

        // Assert
        $response->assertStatus(200);

        $subscriber = NewsletterSubscriber::where('email', $email)->first();
        expect($subscriber->subscribed_at)->toBeInstanceOf(\Carbon\CarbonInterface::class);
        // Allow for microsecond differences by checking the timestamp
        expect($subscriber->subscribed_at->timestamp)->toBeGreaterThanOrEqual($beforeSubscribe->timestamp);
    });

    it('handles duplicate subscription attempt gracefully', function () {
        // Arrange
        $email = 'duplicate@example.com';

        // Act - First subscription
        $response1 = $this->postJson(route('api.v1.newsletter.subscribe'), [
            'email' => $email,
        ]);

        // Act - Second subscription attempt
        $response2 = $this->postJson(route('api.v1.newsletter.subscribe'), [
            'email' => $email,
        ]);

        // Assert
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Verify only one subscriber exists
        expect(NewsletterSubscriber::where('email', $email)->count())->toBe(1);
    });

    it('updates user_id when authenticated user subscribes with different email', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'existing@example.com',
            'user_id' => null,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson(route('api.v1.newsletter.subscribe'), [
                'email' => 'existing@example.com',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
            ])
            ->assertJson([
                'status' => true,
                'message' => __('newsletter.verification_token_sent'),
                'data' => null,
            ]);

        $subscriber->refresh();
        expect($subscriber->user_id)->toBe($user->id);
    });

    it('sets verification_token_expires_at when subscribing', function () {
        // Arrange
        $email = 'expiration@example.com';

        // Act
        $response = $this->postJson(route('api.v1.newsletter.subscribe'), [
            'email' => $email,
        ]);

        // Assert
        $response->assertStatus(200);

        $subscriber = NewsletterSubscriber::where('email', $email)->first();
        expect($subscriber)->not->toBeNull();
        expect($subscriber->verification_token_expires_at)->not->toBeNull();
        expect($subscriber->verification_token_expires_at)->toBeInstanceOf(\Carbon\CarbonInterface::class);
        // Token should expire in the future (default is 24 hours / 1440 minutes)
        expect($subscriber->verification_token_expires_at->isFuture())->toBeTrue();
    });

    it('sets verification_token_expires_at when regenerating token for existing subscriber', function () {
        // Arrange
        $oldToken = Hash::make('old-token');
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'regenerate-expiration@example.com',
            'is_verified' => false,
            'verification_token' => $oldToken,
            'verification_token_expires_at' => now()->subHour(), // Old expired token
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.subscribe'), [
            'email' => 'regenerate-expiration@example.com',
        ]);

        // Assert
        $response->assertStatus(200);

        $subscriber->refresh();
        expect($subscriber->verification_token)->not->toBe($oldToken);
        expect($subscriber->verification_token_expires_at)->not->toBeNull();
        expect($subscriber->verification_token_expires_at->isFuture())->toBeTrue();
    });

    it('sets verification_token_expires_at when resubscribing', function () {
        // Arrange
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'resubscribe-expiration@example.com',
            'is_verified' => false,
            'unsubscribed_at' => now()->subDays(5),
            'verification_token_expires_at' => null, // No token expiration
        ]);

        // Act
        $response = $this->postJson(route('api.v1.newsletter.subscribe'), [
            'email' => 'resubscribe-expiration@example.com',
        ]);

        // Assert
        $response->assertStatus(200);

        $subscriber->refresh();
        expect($subscriber->verification_token_expires_at)->not->toBeNull();
        expect($subscriber->verification_token_expires_at->isFuture())->toBeTrue();
    });
});
