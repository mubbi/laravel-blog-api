<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\Newsletter\NewsletterSubscriberDeletedEvent;
use App\Models\NewsletterSubscriber;
use App\Models\Role;
use App\Models\User;
use App\Services\NewsletterService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

describe('API/V1/Admin/Newsletter/DeleteSubscriberController', function () {
    it('can delete a newsletter subscriber successfully', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.newsletter.subscribers.destroy', $subscriber), [
                'reason' => 'Removed for spam',
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
                'message' => __('common.subscriber_deleted'),
                'data' => null,
            ]);

        // Verify subscriber was deleted from database
        $this->assertDatabaseMissing('newsletter_subscribers', [
            'id' => $subscriber->id,
        ]);
    });

    it('can delete a verified subscriber', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $subscriber = NewsletterSubscriber::factory()->create([
            'is_verified' => true,
            'email' => 'verified@example.com',
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.newsletter.subscribers.destroy', $subscriber), [
                'reason' => 'Removed verified subscriber',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => __('common.subscriber_deleted'),
            ]);

        // Verify subscriber was deleted
        $this->assertDatabaseMissing('newsletter_subscribers', [
            'id' => $subscriber->id,
        ]);
    });

    it('can delete an unverified subscriber', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $subscriber = NewsletterSubscriber::factory()->create([
            'is_verified' => false,
            'email' => 'unverified@example.com',
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.newsletter.subscribers.destroy', $subscriber), [
                'reason' => 'Removed unverified subscriber',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => __('common.subscriber_deleted'),
            ]);

        // Verify subscriber was deleted
        $this->assertDatabaseMissing('newsletter_subscribers', [
            'id' => $subscriber->id,
        ]);
    });

    it('can delete a subscriber without admin note', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.newsletter.subscribers.destroy', $subscriber));

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => __('common.subscriber_deleted'),
            ]);

        // Verify subscriber was deleted
        $this->assertDatabaseMissing('newsletter_subscribers', [
            'id' => $subscriber->id,
        ]);
    });

    it('returns 404 when subscriber does not exist', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $nonExistentId = 99999;

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.newsletter.subscribers.destroy', $nonExistentId), [
                'reason' => 'Test note',
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

    it('returns 403 when user lacks delete_newsletter_subscribers permission', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $newsletterSubscriber = NewsletterSubscriber::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->deleteJson(route('api.v1.admin.newsletter.subscribers.destroy', $newsletterSubscriber), [
                'reason' => 'Test note',
            ]);

        // Assert
        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        // Arrange
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Act
        $response = $this->deleteJson(route('api.v1.admin.newsletter.subscribers.destroy', $subscriber), [
            'reason' => 'Test note',
        ]);

        // Assert
        $response->assertStatus(401);
    });

    it('validates reason field', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.newsletter.subscribers.destroy', $subscriber), [
                'reason' => str_repeat('a', 501), // Exceeds max length
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The reason field must not be greater than 500 characters.',
                'data' => null,
                'error' => [
                    'reason' => ['The reason field must not be greater than 500 characters.'],
                ],
            ]);
    });

    it('handles service exception and logs error', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Mock NewsletterService to throw exception
        $this->mock(NewsletterService::class, function ($mock) {
            $mock->shouldReceive('deleteSubscriber')
                ->andThrow(new \Exception('Service error'));
        });

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.newsletter.subscribers.destroy', $subscriber), [
                'admin_note' => 'Test note',
            ]);

        // Assert
        $response->assertStatus(500)
            ->assertJson([
                'status' => false,
                'message' => __('common.something_went_wrong'),
                'data' => null,
                'error' => null,
            ]);

        // Verify error was logged
        Log::shouldReceive('error')->with(
            'DeleteSubscriberController: Exception occurred',
            \Mockery::type('array')
        );
    });

    it('handles ModelNotFoundException and returns 404', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Mock NewsletterService to throw ModelNotFoundException
        $this->mock(NewsletterService::class, function ($mock) {
            $exception = new ModelNotFoundException;
            $exception->setModel(\App\Models\NewsletterSubscriber::class);
            $mock->shouldReceive('deleteSubscriber')
                ->andThrow($exception);
        });

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.newsletter.subscribers.destroy', $subscriber), [
                'reason' => 'Test note',
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

    it('permanently deletes subscriber from database', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'test@example.com',
            'is_verified' => true,
        ]);

        $subscriberId = $subscriber->id;

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.newsletter.subscribers.destroy', $subscriber), [
                'reason' => 'Permanently deleted',
            ]);

        // Assert
        $response->assertStatus(200);

        // Verify subscriber is completely removed from database
        $this->assertDatabaseMissing('newsletter_subscribers', [
            'id' => $subscriberId,
        ]);

        // Verify no record exists
        $deletedSubscriber = NewsletterSubscriber::find($subscriberId);
        expect($deletedSubscriber)->toBeNull();
    });

    it('dispatches NewsletterSubscriberDeletedEvent when subscriber is deleted', function () {
        // Arrange
        Event::fake([NewsletterSubscriberDeletedEvent::class]);

        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.newsletter.subscribers.destroy', $subscriber), [
                'reason' => 'Test deletion',
            ]);

        // Assert
        $response->assertStatus(200);

        Event::assertDispatched(NewsletterSubscriberDeletedEvent::class, function ($event) use ($subscriber) {
            return $event->subscriberId === $subscriber->id
                && $event->email === $subscriber->email;
        });
    });

    it('dispatches NewsletterSubscriberDeletedEvent with correct data for verified subscriber', function () {
        // Arrange
        Event::fake([NewsletterSubscriberDeletedEvent::class]);

        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'verified@example.com',
            'is_verified' => true,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.newsletter.subscribers.destroy', $subscriber));

        // Assert
        $response->assertStatus(200);

        Event::assertDispatched(NewsletterSubscriberDeletedEvent::class, function ($event) use ($subscriber) {
            return $event->subscriberId === $subscriber->id
                && $event->email === 'verified@example.com';
        });
    });

    it('does not dispatch NewsletterSubscriberDeletedEvent when deletion fails', function () {
        // Arrange
        Event::fake([NewsletterSubscriberDeletedEvent::class]);

        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        // Mock NewsletterService to throw exception before deletion
        $this->mock(NewsletterService::class, function ($mock) {
            $exception = new ModelNotFoundException;
            $exception->setModel(\App\Models\NewsletterSubscriber::class);
            $mock->shouldReceive('deleteSubscriber')
                ->andThrow($exception);
        });

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.newsletter.subscribers.destroy', 99999), [
                'reason' => 'Test note',
            ]);

        // Assert
        $response->assertStatus(404);

        Event::assertNotDispatched(NewsletterSubscriberDeletedEvent::class);
    });

    it('deletes subscriber with user relationship', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $user = User::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'is_verified' => true,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.newsletter.subscribers.destroy', $subscriber), [
                'reason' => 'Deleted with user relationship',
            ]);

        // Assert
        $response->assertStatus(200);

        // Verify subscriber is deleted
        $this->assertDatabaseMissing('newsletter_subscribers', [
            'id' => $subscriber->id,
        ]);

        // Verify related user still exists
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
    });

    it('deletes subscriber without user relationship', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $subscriber = NewsletterSubscriber::factory()->create([
            'user_id' => null,
            'email' => 'guest@example.com',
            'is_verified' => false,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.newsletter.subscribers.destroy', $subscriber), [
                'reason' => 'Deleted guest subscriber',
            ]);

        // Assert
        $response->assertStatus(200);

        // Verify subscriber is deleted
        $this->assertDatabaseMissing('newsletter_subscribers', [
            'id' => $subscriber->id,
        ]);
    });

    it('handles deletion of subscriber with long subscription history', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'longtime@example.com',
            'is_verified' => true,
            'created_at' => now()->subYears(2),
            'updated_at' => now()->subMonths(6),
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.newsletter.subscribers.destroy', $subscriber), [
                'reason' => 'Removed longtime subscriber',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => __('common.subscriber_deleted'),
            ]);

        // Verify subscriber is deleted regardless of subscription history
        $this->assertDatabaseMissing('newsletter_subscribers', [
            'id' => $subscriber->id,
        ]);
    });

    it('prevents deletion of non-existent subscriber with proper error handling', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $nonExistentId = 99999;

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.newsletter.subscribers.destroy', $nonExistentId), [
                'reason' => 'Attempting to delete non-existent subscriber',
            ]);

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => __('common.subscriber_not_found'),
                'data' => null,
                'error' => null,
            ]);

        // Verify no database changes occurred
        $this->assertDatabaseMissing('newsletter_subscribers', [
            'id' => $nonExistentId,
        ]);
    });
});
