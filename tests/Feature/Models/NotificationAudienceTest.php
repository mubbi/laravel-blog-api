<?php

declare(strict_types=1);

use App\Models\Notification;
use App\Models\NotificationAudience;
use App\Models\User;

describe('NotificationAudience Model', function () {
    it('can be created', function () {
        // Arrange
        $notification = Notification::factory()->create();
        $user = User::factory()->create();

        // Act
        $audience = NotificationAudience::factory()->create([
            'notification_id' => $notification->id,
            'audience_type' => 'user',
            'audience_id' => $user->id,
        ]);

        // Assert
        expect($audience->notification_id)->toBe($notification->id);
        expect($audience->audience_type)->toBe('user');
        expect($audience->audience_id)->toBe($user->id);
    });

    it('has notification relationship', function () {
        // Arrange
        $notification = Notification::factory()->create();
        $audience = NotificationAudience::factory()->create([
            'notification_id' => $notification->id,
        ]);

        // Act
        $relatedNotification = $audience->notification;

        // Assert
        expect($relatedNotification)->not->toBeNull();
        expect($relatedNotification->id)->toBe($notification->id);
    });

    it('has user relationship when audience_type is user', function () {
        // Arrange
        $user = User::factory()->create();
        $audience = NotificationAudience::factory()->create([
            'audience_type' => 'user',
            'audience_id' => $user->id,
        ]);

        // Act
        $relatedUser = $audience->user;

        // Assert
        expect($relatedUser)->not->toBeNull();
        expect($relatedUser->id)->toBe($user->id);
    });

    it('can have null audience_id', function () {
        // Arrange
        $audience = NotificationAudience::factory()->create([
            'audience_id' => null,
        ]);

        // Act
        $user = $audience->user;

        // Assert
        expect($user)->toBeNull();
    });

    it('has timestamps', function () {
        // Arrange
        $audience = NotificationAudience::factory()->create();

        // Assert
        expect($audience->created_at)->not->toBeNull();
        expect($audience->updated_at)->not->toBeNull();
    });
});
