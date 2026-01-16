<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('API/V1/Media/GetMediaLibraryController', function () {
    it('returns paginated media library', function () {
        $user = createUserWithRole(UserRole::AUTHOR->value);
        Media::factory()->count(25)->create();

        $response = $this->actingAs($user)
            ->getJson(route('api.v1.media.index'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'media' => ['*' => ['id', 'name', 'file_name', 'mime_type', 'url', 'type']],
                    'meta' => ['current_page', 'per_page', 'total'],
                ],
            ]);
    });

    it('filters media by type', function () {
        $user = createUserWithRole(UserRole::AUTHOR->value);
        Media::factory()->image()->for($user, 'uploader')->count(5)->create();
        Media::factory()->video()->for($user, 'uploader')->count(3)->create();

        $response = $this->actingAs($user)
            ->getJson(route('api.v1.media.index', ['type' => 'image']));

        $response->assertStatus(200);
        $data = $response->json('data.media');
        expect($data)->toHaveCount(5);
        expect($data[0]['type'])->toBe('image');
    });

    it('searches media by name', function () {
        $user = createUserWithRole(UserRole::AUTHOR->value);
        Media::factory()->for($user, 'uploader')->create(['name' => 'Test Image']);
        Media::factory()->for($user, 'uploader')->create(['name' => 'Other Image']);

        $response = $this->actingAs($user)
            ->getJson(route('api.v1.media.index', ['search' => 'Test']));

        $response->assertStatus(200);
        expect($response->json('data.media'))->toHaveCount(1)
            ->and($response->json('data.media.0.name'))->toContain('Test');
    });

    it('non-managers see only their own media', function () {
        $user1 = createUserWithRole(UserRole::AUTHOR->value);
        $user2 = User::factory()->create();
        Media::factory()->for($user1, 'uploader')->count(3)->create();
        Media::factory()->for($user2, 'uploader')->count(2)->create();

        $response = $this->actingAs($user1)
            ->getJson(route('api.v1.media.index'));

        $response->assertStatus(200);
        expect($response->json('data.media'))->toHaveCount(3);
    });

    it('managers see all media', function () {
        $manager = createUserWithRole(UserRole::ADMINISTRATOR->value);
        Media::factory()->count(5)->create();

        $response = $this->actingAs($manager)
            ->getJson(route('api.v1.media.index'));

        $response->assertStatus(200);
        expect($response->json('data.media'))->toHaveCount(5);
    });

    it('requires authentication', function () {
        $response = $this->getJson(route('api.v1.media.index'));

        $response->assertStatus(401);
    });

    it('requires view_media permission', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('api.v1.media.index'));

        $response->assertStatus(403);
    });

    it('validates sort_by parameter', function () {
        $user = createUserWithRole(UserRole::AUTHOR->value);

        $response = $this->actingAs($user)
            ->getJson(route('api.v1.media.index', ['sort_by' => 'invalid']));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sort_by']);
    });
});
