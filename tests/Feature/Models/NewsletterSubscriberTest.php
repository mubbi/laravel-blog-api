<?php

declare(strict_types=1);

use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('NewsletterSubscriber Model', function () {
    it('can be created', function () {
        // Act
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'test@example.com',
            'is_verified' => true,
        ]);

        // Assert
        expect($subscriber->email)->toBe('test@example.com');
        expect($subscriber->is_verified)->toBeTrue();
        expect($subscriber->id)->toBeInt();
    });

    it('has user relationship', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $relatedUser = $subscriber->user;

        // Assert
        expect($relatedUser)->not->toBeNull();
        expect($relatedUser->id)->toBe($user->id);
    });

    it('can have null user_id', function () {
        // Arrange
        $subscriber = NewsletterSubscriber::factory()->create([
            'user_id' => null,
        ]);

        // Act
        $user = $subscriber->user;

        // Assert
        expect($user)->toBeNull();
    });

    it('casts is_verified to boolean', function () {
        // Arrange
        $subscriber = NewsletterSubscriber::factory()->create([
            'is_verified' => 1,
        ]);

        // Assert
        expect($subscriber->is_verified)->toBeTrue();
        expect($subscriber->is_verified)->toBeBool();
    });

    it('casts subscribed_at to datetime', function () {
        // Arrange
        $subscriber = NewsletterSubscriber::factory()->create();

        // Assert
        expect($subscriber->subscribed_at)->toBeInstanceOf(\Carbon\CarbonInterface::class);
    });

    it('casts unsubscribed_at to datetime when set', function () {
        // Arrange
        $subscriber = NewsletterSubscriber::factory()->create([
            'unsubscribed_at' => now(),
        ]);

        // Assert
        expect($subscriber->unsubscribed_at)->toBeInstanceOf(\Carbon\CarbonInterface::class);
    });

    it('has timestamps', function () {
        // Arrange
        $subscriber = NewsletterSubscriber::factory()->create();

        // Assert
        expect($subscriber->created_at)->not->toBeNull();
        expect($subscriber->updated_at)->not->toBeNull();
    });
});
