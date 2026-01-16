<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;

describe('API/V1/User/GetUsersController', function () {
    it('can get paginated list of users', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        User::factory()->count(5)->create();

        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.users.index'));

        expect($response)->toHaveApiSuccessStructure([
            'users' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'avatar_url',
                    'bio',
                    'twitter',
                    'facebook',
                    'linkedin',
                    'github',
                    'website',
                    'banned_at',
                    'blocked_at',
                    'created_at',
                    'updated_at',
                    'roles',
                    'articles_count',
                    'comments_count',
                    'status',
                ],
            ],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'per_page',
                'to',
                'total',
            ],
        ]);
    });

    it('can filter users by search term', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $user1 = User::factory()->create(['name' => 'TestSearch Doe']);
        $user2 = User::factory()->create(['name' => 'Jane Smith']);
        $user3 = User::factory()->create(['name' => 'Bob TestSearched']);

        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.users.index', ['search' => 'TestSearch']));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('data.users'))->toHaveCount(2);
    });

    it('can filter users by role', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $authorRole = Role::where('name', UserRole::AUTHOR->value)->first();
        $user1 = User::factory()->create();
        $user1->roles()->attach($authorRole->id);
        $user2 = User::factory()->create();

        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.users.index', ['role_id' => $authorRole->id]));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('data.users'))->toHaveCount(1)
            ->and($response->json('data.users.0.id'))->toBe($user1->id);
    });

    it('can filter users by status', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $bannedUser = User::factory()->create(['banned_at' => now()]);
        $blockedUser = User::factory()->create(['blocked_at' => now()]);
        $activeUser = User::factory()->create();

        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.users.index', ['status' => 'banned']));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('data.users'))->toHaveCount(1)
            ->and($response->json('data.users.0.id'))->toBe($bannedUser->id)
            ->and($response->json('data.users.0.status'))->toBe('banned');
    });

    it('can sort users by different fields', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $user1 = User::factory()->create(['name' => 'Alice']);
        $user2 = User::factory()->create(['name' => 'Bob']);
        $user3 = User::factory()->create(['name' => 'Charlie']);

        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.users.index', [
                'sort_by' => 'name',
                'sort_direction' => 'desc',
            ]));

        expect($response->getStatusCode())->toBe(200);
        $userNames = collect($response->json('data.users'))->pluck('name')->toArray();
        expect(in_array('Charlie', $userNames))->toBeTrue()
            ->and(in_array('Bob', $userNames))->toBeTrue()
            ->and(in_array('Alice', $userNames))->toBeTrue();
    });

    it('can paginate users', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        User::factory()->count(15)->create();

        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.users.index', ['per_page' => 10, 'page' => 2]));

        expect($response->getStatusCode())->toBe(200);
        $meta = $response->json('data.meta');
        expect($meta['current_page'])->toBe(2)
            ->and($meta['per_page'])->toBe(10)
            ->and($meta['total'])->toBeGreaterThanOrEqual(15);
    });

    it('returns 403 when user lacks view_users permission', function () {
        $user = createUserWithRole(UserRole::SUBSCRIBER->value);

        $response = $this->actingAs($user)
            ->getJson(route('api.v1.users.index'));

        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        $response = $this->getJson(route('api.v1.users.index'));

        $response->assertStatus(401);
    });
});
