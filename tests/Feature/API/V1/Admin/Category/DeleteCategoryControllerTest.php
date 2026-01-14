<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;

describe('API/V1/Admin/Category/DeleteCategoryController', function () {
    it('can delete a category successfully', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $category = Category::factory()->create();

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.categories.destroy', $category));

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => __('common.category_deleted_successfully'),
            ]);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    });

    it('moves children to parent when delete_children is false', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $parent = Category::factory()->create();
        $category = Category::factory()->create(['parent_id' => $parent->id]);
        $child1 = Category::factory()->create(['parent_id' => $category->id]);
        $child2 = Category::factory()->create(['parent_id' => $category->id]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.categories.destroy', $category), [
                'delete_children' => false,
            ]);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $child1->id,
            'parent_id' => $parent->id,
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $child2->id,
            'parent_id' => $parent->id,
        ]);
    });

    it('deletes children recursively when delete_children is true', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $category = Category::factory()->create();
        $child1 = Category::factory()->create(['parent_id' => $category->id]);
        $child2 = Category::factory()->create(['parent_id' => $category->id]);
        $grandchild = Category::factory()->create(['parent_id' => $child1->id]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.categories.destroy', $category), [
                'delete_children' => true,
            ]);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);

        $this->assertDatabaseMissing('categories', [
            'id' => $child1->id,
        ]);

        $this->assertDatabaseMissing('categories', [
            'id' => $child2->id,
        ]);

        $this->assertDatabaseMissing('categories', [
            'id' => $grandchild->id,
        ]);
    });

    it('makes children root when deleting root category with delete_children false', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $category = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $category->id]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.categories.destroy', $category), [
                'delete_children' => false,
            ]);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('categories', [
            'id' => $child->id,
            'parent_id' => null,
        ]);
    });

    it('requires delete_categories permission', function () {
        // Arrange
        $user = User::factory()->create();
        $authorRole = Role::where('name', UserRole::AUTHOR->value)->first();
        attachRoleAndRefreshCache($user, $authorRole);

        $category = Category::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->deleteJson(route('api.v1.admin.categories.destroy', $category));

        // Assert
        $response->assertStatus(403);
    });
});
