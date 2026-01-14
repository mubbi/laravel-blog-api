<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;

describe('API/V1/Admin/Tag/UpdateTagController', function () {
    it('can update a tag successfully', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $tag = Tag::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-slug',
        ]);

        $updateData = [
            'name' => 'New Name',
            'slug' => 'new-slug',
        ];

        // Act
        $response = $this->actingAs($admin)
            ->putJson(route('api.v1.admin.tags.update', $tag), $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $tag->id,
                    'name' => 'New Name',
                    'slug' => 'new-slug',
                ],
            ]);

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'New Name',
            'slug' => 'new-slug',
        ]);
    });

    it('requires edit_tags permission', function () {
        // Arrange
        $user = User::factory()->create();
        $authorRole = Role::where('name', UserRole::AUTHOR->value)->first();
        attachRoleAndRefreshCache($user, $authorRole);

        $tag = Tag::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.v1.admin.tags.update', $tag), ['name' => 'New Name']);

        // Assert
        $response->assertStatus(403);
    });
});
