<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;

describe('API/V1/Admin/Tag/DeleteTagController', function () {
    it('can delete a tag successfully', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $tag = Tag::factory()->create();

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.tags.destroy', $tag));

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => __('common.tag_deleted_successfully'),
            ]);

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    });

    it('requires delete_tags permission', function () {
        // Arrange
        $user = User::factory()->create();
        $authorRole = Role::where('name', UserRole::AUTHOR->value)->first();
        attachRoleAndRefreshCache($user, $authorRole);

        $tag = Tag::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->deleteJson(route('api.v1.admin.tags.destroy', $tag));

        // Assert
        $response->assertStatus(403);
    });
});
