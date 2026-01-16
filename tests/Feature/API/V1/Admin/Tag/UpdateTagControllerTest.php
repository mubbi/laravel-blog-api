<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Tag;

describe('API/V1/Admin/Tag/UpdateTagController', function () {
    it('can update a tag successfully', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $tag = Tag::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-slug',
        ]);

        $response = $this->actingAs($admin)
            ->putJson(route('api.v1.admin.tags.update', $tag), [
                'name' => 'New Name',
                'slug' => 'new-slug',
            ]);

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.id'))->toBe($tag->id)
            ->and($response->json('data.name'))->toBe('New Name')
            ->and($response->json('data.slug'))->toBe('new-slug');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'New Name',
            'slug' => 'new-slug',
        ]);
    });

    it('requires edit_tags permission', function () {
        $user = createUserWithRole(UserRole::AUTHOR->value);
        $tag = Tag::factory()->create();

        $response = $this->actingAs($user)
            ->putJson(route('api.v1.admin.tags.update', $tag), ['name' => 'New Name']);

        $response->assertStatus(403);
    });
});
