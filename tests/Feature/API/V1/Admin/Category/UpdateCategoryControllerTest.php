<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;

describe('API/V1/Category/UpdateCategoryController', function () {
    it('can update a category successfully', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $category = Category::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-slug',
        ]);

        $response = $this->actingAs($admin)
            ->putJson(route('api.v1.categories.update', $category), [
                'name' => 'New Name',
                'slug' => 'new-slug',
            ]);

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.id'))->toBe($category->id)
            ->and($response->json('data.name'))->toBe('New Name')
            ->and($response->json('data.slug'))->toBe('new-slug');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'New Name',
            'slug' => 'new-slug',
        ]);
    });

    it('can update category with parent', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $parentCategory = Category::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($admin)
            ->putJson(route('api.v1.categories.update', $category), [
                'parent_id' => $parentCategory->id,
            ]);

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'parent_id' => $parentCategory->id,
        ]);
    });

    it('can remove parent by setting parent_id to null', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $parentCategory = Category::factory()->create();
        $category = Category::factory()->create(['parent_id' => $parentCategory->id]);

        $response = $this->actingAs($admin)
            ->putJson(route('api.v1.categories.update', $category), [
                'parent_id' => null,
            ]);

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'parent_id' => null,
        ]);
    });

    it('prevents circular reference when updating parent', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);
        $grandchild = Category::factory()->create(['parent_id' => $child->id]);

        // Try to set parent's parent_id to grandchild (circular)
        $updateData = [
            'parent_id' => $grandchild->id,
        ];

        // Act
        $response = $this->actingAs($admin)
            ->putJson(route('api.v1.categories.update', $parent), $updateData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    });

    it('prevents category from being its own parent', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $category = Category::factory()->create();

        $updateData = [
            'parent_id' => $category->id,
        ];

        // Act
        $response = $this->actingAs($admin)
            ->putJson(route('api.v1.categories.update', $category), $updateData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    });

    it('requires edit_categories permission', function () {
        // Arrange
        $user = User::factory()->create();
        $authorRole = Role::where('name', UserRole::AUTHOR->value)->first();
        attachRoleAndRefreshCache($user, $authorRole);

        $category = Category::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.v1.categories.update', $category), ['name' => 'New Name']);

        // Assert
        $response->assertStatus(403);
    });
});
