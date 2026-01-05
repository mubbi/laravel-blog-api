<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;

describe('API/V1/Admin/Tag/CreateTagController', function () {
    it('can create a tag with valid data', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $tagData = [
            'name' => 'PHP',
            'slug' => 'php',
        ];

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.tags.store'), $tagData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'slug',
                ],
            ])
            ->assertJson([
                'status' => true,
                'data' => [
                    'name' => 'PHP',
                    'slug' => 'php',
                ],
            ]);

        $this->assertDatabaseHas('tags', [
            'name' => 'PHP',
            'slug' => 'php',
        ]);
    });

    it('auto-generates slug from name if not provided', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $tagData = [
            'name' => 'JavaScript Framework',
        ];

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.tags.store'), $tagData);

        // Assert
        $response->assertStatus(201);

        $this->assertDatabaseHas('tags', [
            'name' => 'JavaScript Framework',
            'slug' => 'javascript-framework',
        ]);
    });

    it('requires authentication', function () {
        $tagData = [
            'name' => 'PHP',
        ];

        $response = $this->postJson(route('api.v1.admin.tags.store'), $tagData);

        $response->assertStatus(401);
    });

    it('requires create_tags permission', function () {
        // Arrange
        $user = User::factory()->create();
        $authorRole = Role::where('name', UserRole::AUTHOR->value)->first();
        attachRoleAndRefreshCache($user, $authorRole);

        $tagData = [
            'name' => 'PHP',
        ];

        // Act
        $response = $this->actingAs($user)
            ->postJson(route('api.v1.admin.tags.store'), $tagData);

        // Assert
        $response->assertStatus(403);
    });

    it('validates unique name', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        Tag::factory()->create(['name' => 'PHP']);

        $tagData = [
            'name' => 'PHP',
        ];

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.tags.store'), $tagData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('validates unique slug', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        Tag::factory()->create(['slug' => 'php']);

        $tagData = [
            'name' => 'PHP Framework',
            'slug' => 'php',
        ];

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.tags.store'), $tagData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    });

});
