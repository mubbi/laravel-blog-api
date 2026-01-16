<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\NewsletterSubscriber;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Log;

describe('API/V1/Newsletter/GetSubscribersController', function () {
    it('can get paginated list of newsletter subscribers', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        NewsletterSubscriber::factory()->count(5)->create();

        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.newsletter.subscribers.index'));

        expect($response)->toHaveApiSuccessStructure([
            'subscribers' => [
                '*' => [
                    'id',
                    'email',
                    'user_id',
                    'is_verified',
                    'subscribed_at',
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => [
                'current_page',
                'per_page',
                'total',
                'last_page',
                'from',
                'to',
            ],
        ]);
    });

    it('can filter subscribers by search term', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        NewsletterSubscriber::factory()->create(['email' => 'john@example.com']);
        NewsletterSubscriber::factory()->create(['email' => 'jane@example.com']);
        NewsletterSubscriber::factory()->create(['email' => 'bob@test.com']);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.newsletter.subscribers.index', ['search' => 'example']));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.subscribers');
        expect($data)->toHaveCount(2); // john@example.com and jane@example.com
    });

    it('can filter subscribers by verification status', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        NewsletterSubscriber::factory()->create(['is_verified' => true]);
        NewsletterSubscriber::factory()->create(['is_verified' => false]);
        NewsletterSubscriber::factory()->create(['is_verified' => true]);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.newsletter.subscribers.index', ['status' => 'verified']));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.subscribers');
        expect($data)->toHaveCount(2);
        foreach ($data as $subscriber) {
            expect($subscriber['is_verified'])->toBeTrue();
        }
    });

    it('can filter subscribers by subscription date range', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $oldSubscriber = NewsletterSubscriber::factory()->create([
            'created_at' => now()->subDays(10),
        ]);
        $recentSubscriber = NewsletterSubscriber::factory()->create([
            'created_at' => now()->subDays(2),
        ]);
        $newSubscriber = NewsletterSubscriber::factory()->create([
            'created_at' => now(),
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.newsletter.subscribers.index', [
                'subscribed_at_from' => now()->subDays(5)->toDateString(),
                'subscribed_at_to' => now()->subDays(1)->toDateString(),
            ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.subscribers');
        expect($data)->toHaveCount(1);
        expect($data[0]['id'])->toBe($recentSubscriber->id);
    });

    it('can sort subscribers by different fields', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        NewsletterSubscriber::factory()->create(['email' => 'alice@example.com']);
        NewsletterSubscriber::factory()->create(['email' => 'bob@example.com']);
        NewsletterSubscriber::factory()->create(['email' => 'charlie@example.com']);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.newsletter.subscribers.index', [
                'sort_by' => 'email',
                'sort_order' => 'desc',
            ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.subscribers');
        $emails = collect($data)->pluck('email')->toArray();
        expect($emails)->toBe(['charlie@example.com', 'bob@example.com', 'alice@example.com']);
    });

    it('can paginate subscribers', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        NewsletterSubscriber::factory()->count(25)->create();

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.newsletter.subscribers.index', [
                'per_page' => 10,
                'page' => 2,
            ]));

        // Assert
        $response->assertStatus(200);
        $meta = $response->json('data.meta');
        expect($meta['current_page'])->toBe(2);
        expect($meta['per_page'])->toBe(10);
        expect($meta['total'])->toBeGreaterThanOrEqual(25);
    });

    it('returns 403 when user lacks view_newsletter_subscribers permission', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        // Act
        $response = $this->actingAs($user)
            ->getJson(route('api.v1.newsletter.subscribers.index'));

        // Assert
        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        // Act
        $response = $this->getJson(route('api.v1.newsletter.subscribers.index'));

        // Assert
        $response->assertStatus(401);
    });

    it('handles empty results gracefully', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.newsletter.subscribers.index'));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.subscribers');
        expect($data)->toBeArray();
        expect($data)->toHaveCount(0);
    });

    it('handles service exception and logs error', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        // Mock NewsletterService to throw exception
        $this->mock(\App\Services\Interfaces\NewsletterServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getSubscribers')
                ->andThrow(new \Exception('Database error'));
        });

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.newsletter.subscribers.index'));

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
            'GetSubscribersController: Exception occurred',
            \Mockery::type('array')
        );
    });

    it('includes subscriber with user relationship when available', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $user = User::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.newsletter.subscribers.index'));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.subscribers');
        $foundSubscriber = collect($data)->firstWhere('id', $subscriber->id);
        expect($foundSubscriber)->not->toBeNull();
        expect($foundSubscriber['user_id'])->toBe($user->id);
        expect($foundSubscriber['email'])->toBe($user->email);
    });

    it('handles subscribers without user relationship', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $subscriber = NewsletterSubscriber::factory()->create([
            'user_id' => null,
            'email' => 'guest@example.com',
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.newsletter.subscribers.index'));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.subscribers');
        $foundSubscriber = collect($data)->firstWhere('id', $subscriber->id);
        expect($foundSubscriber)->not->toBeNull();
        expect($foundSubscriber['user_id'])->toBeNull();
        expect($foundSubscriber['email'])->toBe('guest@example.com');
    });

    it('validates date format for subscription date filters', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.newsletter.subscribers.index', [
                'subscribed_at_from' => 'invalid-date',
                'subscribed_at_to' => 'invalid-date',
            ]));

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The subscribed at from field must be a valid date. (and 1 more error)',
                'data' => null,
                'error' => [
                    'subscribed_at_from' => ['The subscribed at from field must be a valid date.'],
                    'subscribed_at_to' => ['The subscribed at to field must be a valid date.'],
                ],
            ]);
    });

    it('handles large result sets efficiently', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        NewsletterSubscriber::factory()->count(100)->create();

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.newsletter.subscribers.index', [
                'per_page' => 50,
            ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.subscribers');
        $meta = $response->json('data.meta');
        expect($data)->toHaveCount(50);
        expect($meta['total'])->toBeGreaterThanOrEqual(100);
        expect($meta['per_page'])->toBe(50);
    });

    it('filters by multiple criteria simultaneously', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        // Create subscribers with different characteristics
        NewsletterSubscriber::factory()->create([
            'email' => 'verified@example.com',
            'is_verified' => true,
            'created_at' => now()->subDays(5),
        ]);
        NewsletterSubscriber::factory()->create([
            'email' => 'unverified@example.com',
            'is_verified' => false,
            'created_at' => now()->subDays(5),
        ]);
        NewsletterSubscriber::factory()->create([
            'email' => 'recent@example.com',
            'is_verified' => true,
            'created_at' => now()->subDays(1),
        ]);

        // Act - Filter for verified subscribers from the last week
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.newsletter.subscribers.index', [
                'status' => 'verified',
                'subscribed_at_from' => now()->subWeek()->toDateString(),
                'subscribed_at_to' => now()->toDateString(),
            ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.subscribers');
        expect($data)->toHaveCount(2); // verified@example.com and recent@example.com
        foreach ($data as $subscriber) {
            expect($subscriber['is_verified'])->toBeTrue();
        }
    });
});
