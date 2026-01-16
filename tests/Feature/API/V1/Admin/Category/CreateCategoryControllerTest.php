<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Category;

describe('API/V1/Category/CreateCategoryController', function () {
    it('can create a category with valid data', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $categoryData = [
            'name' => 'Technology',
            'slug' => 'technology',
        ];

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.categories.store'), $categoryData);

        expect($response->getStatusCode())->toBe(201)
            ->and($response)->toHaveApiSuccessStructure([
                'id',
                'name',
                'slug',
                'parent_id',
            ])->and($response->json('data.name'))->toBe('Technology')
            ->and($response->json('data.slug'))->toBe('technology')
            ->and($response->json('data.parent_id'))->toBeNull();

        $this->assertDatabaseHas('categories', [
            'name' => 'Technology',
            'slug' => 'technology',
            'parent_id' => null,
        ]);
    });

    it('can create a category with parent', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $parentCategory = Category::factory()->create();

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.categories.store'), [
                'name' => 'Web Development',
                'slug' => 'web-development',
                'parent_id' => $parentCategory->id,
            ]);

        expect($response->getStatusCode())->toBe(201)
            ->and($response->json('data.parent_id'))->toBe($parentCategory->id);

        $this->assertDatabaseHas('categories', [
            'name' => 'Web Development',
            'slug' => 'web-development',
            'parent_id' => $parentCategory->id,
        ]);
    });

    it('auto-generates slug from name if not provided', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.categories.store'), [
                'name' => 'Mobile Development',
            ]);

        expect($response->getStatusCode())->toBe(201);
        $this->assertDatabaseHas('categories', [
            'name' => 'Mobile Development',
            'slug' => 'mobile-development',
        ]);
    });

    it('requires authentication', function () {
        $response = $this->postJson(route('api.v1.categories.store'), [
            'name' => 'Technology',
        ]);

        $response->assertStatus(401);
    });

    it('requires create_categories permission', function () {
        $user = createUserWithRole(UserRole::AUTHOR->value);

        $response = $this->actingAs($user)
            ->postJson(route('api.v1.categories.store'), [
                'name' => 'Technology',
            ]);

        $response->assertStatus(403);
    });

    it('validates unique name', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        Category::factory()->create(['name' => 'Technology']);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.categories.store'), [
                'name' => 'Technology',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('validates unique slug', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        Category::factory()->create(['slug' => 'technology']);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.categories.store'), [
                'name' => 'Tech',
                'slug' => 'technology',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    });

    it('validates parent_id exists', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.categories.store'), [
                'name' => 'Child Category',
                'parent_id' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    });
});
