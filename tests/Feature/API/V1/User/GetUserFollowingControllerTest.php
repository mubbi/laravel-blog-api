<?php

declare(strict_types=1);

use App\Models\User;

describe('API/V1/User/GetUserFollowingController', function () {
    it('can get paginated list of users that a user is following', function () {
        // Arrange
        $user = User::factory()->create();
        $following = User::factory()->count(5)->create();

        // Create follow relationships
        foreach ($following as $followedUser) {
            $user->following()->attach($followedUser->id);
        }

        // Act
        $response = $this->getJson(route('api.v1.users.following', ['user' => $user->id]));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'following' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'avatar_url',
                            'bio',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'meta' => [
                        'current_page',
                        'per_page',
                        'total',
                    ],
                ],
            ])
            ->assertJson([
                'status' => true,
                'message' => __('common.success'),
            ]);

        $responseData = $response->json('data');
        expect($responseData['following'])->toHaveCount(5);
        expect($responseData['meta']['total'])->toBe(5);
    });

    it('returns empty list when user is not following anyone', function () {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->getJson(route('api.v1.users.following', ['user' => $user->id]));

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
            ]);

        $responseData = $response->json('data');
        expect($responseData['following'])->toHaveCount(0);
        expect($responseData['meta']['total'])->toBe(0);
    });

    it('supports pagination', function () {
        // Arrange
        $user = User::factory()->create();
        $following = User::factory()->count(20)->create();

        // Create follow relationships
        foreach ($following as $followedUser) {
            $user->following()->attach($followedUser->id);
        }

        // Act
        $response = $this->getJson(route('api.v1.users.following', [
            'user' => $user->id,
            'page' => 1,
            'per_page' => 10,
        ]));

        // Assert
        $response->assertStatus(200);
        $responseData = $response->json('data');
        expect($responseData['following'])->toHaveCount(10);
        expect($responseData['meta']['total'])->toBe(20);
        expect($responseData['meta']['per_page'])->toBe(10);
    });

    it('supports sorting', function () {
        // Arrange
        $user = User::factory()->create();
        $user1 = User::factory()->create(['name' => 'Alice']);
        $user2 = User::factory()->create(['name' => 'Bob']);
        $user3 = User::factory()->create(['name' => 'Charlie']);

        // Create follow relationships
        $user->following()->attach($user1->id);
        $user->following()->attach($user2->id);
        $user->following()->attach($user3->id);

        // Act
        $response = $this->getJson(route('api.v1.users.following', [
            'user' => $user->id,
            'sort_by' => 'name',
            'sort_direction' => 'asc',
        ]));

        // Assert
        $response->assertStatus(200);
        $responseData = $response->json('data');
        $names = collect($responseData['following'])->pluck('name')->toArray();
        expect($names)->toBe(['Alice', 'Bob', 'Charlie']);
    });

    it('returns 404 when user does not exist', function () {
        // Act
        $response = $this->getJson(route('api.v1.users.following', ['user' => 99999]));

        // Assert
        $response->assertStatus(404);
    });
});
