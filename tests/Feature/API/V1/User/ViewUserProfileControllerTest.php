<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Comment;
use App\Models\Role;
use App\Models\User;

describe('API/V1/User/ViewUserProfileController', function () {
    it('can view user profile with all relationships', function () {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'bio' => 'Test bio',
        ]);

        $role = Role::firstOrCreate(['name' => 'Author'], ['slug' => 'author']);
        $user->roles()->attach($role->id);

        // Create some articles and comments
        Article::factory()->count(3)->for($user, 'author')->create();
        Comment::factory()->count(5)->for($user)->create();

        // Create followers and following
        $follower = User::factory()->create();
        $following = User::factory()->create();
        $follower->following()->attach($user->id);
        $user->following()->attach($following->id);

        // Act
        $response = $this->getJson(route('api.v1.users.profile', ['user' => $user->id]));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'avatar_url',
                    'bio',
                    'articles_count',
                    'comments_count',
                    'followers_count',
                    'following_count',
                    'roles',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'status' => true,
                'message' => __('common.success'),
                'data' => [
                    'id' => $user->id,
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'bio' => 'Test bio',
                ],
            ]);

        $responseData = $response->json('data');
        expect($responseData['articles_count'])->toBe(3);
        expect($responseData['comments_count'])->toBe(5);
        expect($responseData['followers_count'])->toBe(1);
        expect($responseData['following_count'])->toBe(1);
        expect($responseData['roles'])->toBeArray();
    });

    it('returns profile with zero counts when user has no activity', function () {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->getJson(route('api.v1.users.profile', ['user' => $user->id]));

        // Assert
        $response->assertStatus(200);
        $responseData = $response->json('data');
        expect($responseData['articles_count'])->toBe(0);
        expect($responseData['comments_count'])->toBe(0);
        expect($responseData['followers_count'])->toBe(0);
        expect($responseData['following_count'])->toBe(0);
    });

    it('includes roles in profile response', function () {
        // Arrange
        $user = User::factory()->create();
        $role1 = Role::firstOrCreate(['name' => 'Author'], ['slug' => 'author']);
        $role2 = Role::firstOrCreate(['name' => 'Editor'], ['slug' => 'editor']);
        $user->roles()->attach([$role1->id, $role2->id]);

        // Act
        $response = $this->getJson(route('api.v1.users.profile', ['user' => $user->id]));

        // Assert
        $response->assertStatus(200);
        $responseData = $response->json('data');
        expect($responseData['roles'])->toHaveCount(2);
        expect($responseData['roles'][0])->toHaveKeys(['id', 'name', 'slug']);
    });

    it('returns 404 when user does not exist', function () {
        // Act
        $response = $this->getJson(route('api.v1.users.profile', ['user' => 99999]));

        // Assert
        $response->assertStatus(404);
    });

    it('can be accessed without authentication', function () {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->getJson(route('api.v1.users.profile', ['user' => $user->id]));

        // Assert
        $response->assertStatus(200);
    });
});
