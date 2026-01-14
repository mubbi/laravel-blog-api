<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;

describe('API/V1/Admin/Category/CreateCategoryController', function () {
    it('can create a category with valid data', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $categoryData = [
            'name' => 'Technology',
            'slug' => 'technology',
        ];

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.categories.store'), $categoryData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'parent_id',
                ],
            ])
            ->assertJson([
                'status' => true,
                'data' => [
                    'name' => 'Technology',
                    'slug' => 'technology',
                    'parent_id' => null,
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Technology',
            'slug' => 'technology',
            'parent_id' => null,
        ]);
    });

    it('can create a category with parent', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $parentCategory = Category::factory()->create();

        $categoryData = [
            'name' => 'Web Development',
            'slug' => 'web-development',
            'parent_id' => $parentCategory->id,
        ];

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.categories.store'), $categoryData);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'status' => true,
                'data' => [
                    'name' => 'Web Development',
                    'slug' => 'web-development',
                    'parent_id' => $parentCategory->id,
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Web Development',
            'slug' => 'web-development',
            'parent_id' => $parentCategory->id,
        ]);
    });

    it('auto-generates slug from name if not provided', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $categoryData = [
            'name' => 'Mobile Development',
        ];

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.categories.store'), $categoryData);

        // Assert
        $response->assertStatus(201);

        $this->assertDatabaseHas('categories', [
            'name' => 'Mobile Development',
            'slug' => 'mobile-development',
        ]);
    });

    it('requires authentication', function () {
        $categoryData = [
            'name' => 'Technology',
        ];

        $response = $this->postJson(route('api.v1.admin.categories.store'), $categoryData);

        $response->assertStatus(401);
    });

    it('requires create_categories permission', function () {
        // Arrange
        $user = User::factory()->create();
        $authorRole = Role::where('name', UserRole::AUTHOR->value)->first();
        attachRoleAndRefreshCache($user, $authorRole);

        $categoryData = [
            'name' => 'Technology',
        ];

        // Act
        $response = $this->actingAs($user)
            ->postJson(route('api.v1.admin.categories.store'), $categoryData);

        // Assert
        $response->assertStatus(403);
    });

    it('validates unique name', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        Category::factory()->create(['name' => 'Technology']);

        $categoryData = [
            'name' => 'Technology',
        ];

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.categories.store'), $categoryData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('validates unique slug', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        Category::factory()->create(['slug' => 'technology']);

        $categoryData = [
            'name' => 'Tech',
            'slug' => 'technology',
        ];

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.categories.store'), $categoryData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    });

    it('validates parent_id exists', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $categoryData = [
            'name' => 'Child Category',
            'parent_id' => 99999,
        ];

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.categories.store'), $categoryData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    });
});
