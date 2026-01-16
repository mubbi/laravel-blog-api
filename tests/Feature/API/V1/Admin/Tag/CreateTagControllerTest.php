<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Tag;

describe('API/V1/Admin/Tag/CreateTagController', function () {
    it('can create a tag with valid data', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $tagData = [
            'name' => 'PHP',
            'slug' => 'php',
        ];

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.tags.store'), $tagData);

        expect($response->getStatusCode())->toBe(201)
            ->and($response)->toHaveApiSuccessStructure([
                'id',
                'name',
                'slug',
            ])->and($response->json('data.name'))->toBe('PHP')
            ->and($response->json('data.slug'))->toBe('php');

        $this->assertDatabaseHas('tags', [
            'name' => 'PHP',
            'slug' => 'php',
        ]);
    });

    it('auto-generates slug from name if not provided', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.tags.store'), [
                'name' => 'JavaScript Framework',
            ]);

        expect($response->getStatusCode())->toBe(201);
        $this->assertDatabaseHas('tags', [
            'name' => 'JavaScript Framework',
            'slug' => 'javascript-framework',
        ]);
    });

    it('requires authentication', function () {
        $response = $this->postJson(route('api.v1.admin.tags.store'), [
            'name' => 'PHP',
        ]);

        $response->assertStatus(401);
    });

    it('requires create_tags permission', function () {
        $user = createUserWithRole(UserRole::AUTHOR->value);

        $response = $this->actingAs($user)
            ->postJson(route('api.v1.admin.tags.store'), [
                'name' => 'PHP',
            ]);

        $response->assertStatus(403);
    });

    it('validates unique name', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        Tag::factory()->create(['name' => 'PHP']);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.tags.store'), [
                'name' => 'PHP',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('validates unique slug', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        Tag::factory()->create(['slug' => 'php']);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.tags.store'), [
                'name' => 'PHP Framework',
                'slug' => 'php',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    });

});
