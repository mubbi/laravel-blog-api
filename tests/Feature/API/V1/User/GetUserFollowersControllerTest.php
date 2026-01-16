<?php

declare(strict_types=1);

use App\Models\User;

describe('API/V1/User/GetUserFollowersController', function () {
    it('can get paginated list of user followers', function () {
        $user = User::factory()->create();
        $followers = User::factory()->count(5)->create();

        foreach ($followers as $follower) {
            $follower->following()->attach($user->id);
        }

        $response = $this->getJson(route('api.v1.users.followers', ['user' => $user->id]));

        expect($response)->toHaveApiSuccessStructure([
            'followers' => [
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
        ])->and($response->json('data.followers'))->toHaveCount(5)
            ->and($response->json('data.meta.total'))->toBe(5);
    });

    it('returns empty list when user has no followers', function () {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->getJson(route('api.v1.users.followers', ['user' => $user->id]));

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
            ]);

        $responseData = $response->json('data');
        expect($responseData['followers'])->toHaveCount(0);
        expect($responseData['meta']['total'])->toBe(0);
    });

    it('supports pagination', function () {
        // Arrange
        $user = User::factory()->create();
        $followers = User::factory()->count(20)->create();

        // Create follow relationships
        foreach ($followers as $follower) {
            $follower->following()->attach($user->id);
        }

        // Act
        $response = $this->getJson(route('api.v1.users.followers', [
            'user' => $user->id,
            'page' => 1,
            'per_page' => 10,
        ]));

        // Assert
        $response->assertStatus(200);
        $responseData = $response->json('data');
        expect($responseData['followers'])->toHaveCount(10);
        expect($responseData['meta']['total'])->toBe(20);
        expect($responseData['meta']['per_page'])->toBe(10);
    });

    it('supports sorting', function () {
        // Arrange
        $user = User::factory()->create();
        $follower1 = User::factory()->create(['name' => 'Alice']);
        $follower2 = User::factory()->create(['name' => 'Bob']);
        $follower3 = User::factory()->create(['name' => 'Charlie']);

        // Create follow relationships
        $follower1->following()->attach($user->id);
        $follower2->following()->attach($user->id);
        $follower3->following()->attach($user->id);

        // Act
        $response = $this->getJson(route('api.v1.users.followers', [
            'user' => $user->id,
            'sort_by' => 'name',
            'sort_direction' => 'asc',
        ]));

        // Assert
        $response->assertStatus(200);
        $responseData = $response->json('data');
        $names = collect($responseData['followers'])->pluck('name')->toArray();
        expect($names)->toBe(['Alice', 'Bob', 'Charlie']);
    });

    it('returns 404 when user does not exist', function () {
        // Act
        $response = $this->getJson(route('api.v1.users.followers', ['user' => 99999]));

        // Assert
        $response->assertStatus(404);
    });
});
