<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Comment;
use App\Models\Role;
use App\Models\User;

describe('API/V1/User/ViewUserProfileController', function () {
    it('can view user profile with all relationships', function () {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'bio' => 'Test bio',
        ]);

        $role = Role::firstOrCreate(['name' => 'Author'], ['slug' => 'author']);
        $user->roles()->attach($role->id);

        Article::factory()->count(3)->for($user, 'author')->create();
        Comment::factory()->count(5)->for($user)->create();

        $follower = User::factory()->create();
        $following = User::factory()->create();
        $follower->following()->attach($user->id);
        $user->following()->attach($following->id);

        $response = $this->getJson(route('api.v1.users.profile', ['user' => $user->id]));

        expect($response)->toHaveApiSuccessStructure([
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
        ])->and($response->json('data.id'))->toBe($user->id)
            ->and($response->json('data.name'))->toBe('Test User')
            ->and($response->json('data.email'))->toBe('test@example.com')
            ->and($response->json('data.bio'))->toBe('Test bio')
            ->and($response->json('data.articles_count'))->toBe(3)
            ->and($response->json('data.comments_count'))->toBe(5)
            ->and($response->json('data.followers_count'))->toBe(1)
            ->and($response->json('data.following_count'))->toBe(1)
            ->and($response->json('data.roles'))->toBeArray();
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
