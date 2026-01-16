<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Media;
use App\Models\User;

describe('API/V1/Media/GetMediaDetailsController', function () {
    it('returns media details', function () {
        $user = createUserWithRole(UserRole::AUTHOR->value);
        $media = Media::factory()->for($user, 'uploader')->create();

        $response = $this->actingAs($user)
            ->getJson(route('api.v1.media.show', $media));

        expect($response)->toHaveApiSuccessStructure([
            'id', 'name', 'file_name', 'mime_type', 'url', 'type', 'uploader',
        ])->and($response->json('data.id'))->toBe($media->id)
            ->and($response->json('data.name'))->toBe($media->name);
    });

    it('returns 404 when media does not exist', function () {
        $user = createUserWithRole(UserRole::AUTHOR->value);

        $response = $this->actingAs($user)
            ->getJson(route('api.v1.media.show', 99999));

        $response->assertStatus(404);
    });

    it('requires authentication', function () {
        $media = Media::factory()->create();

        $response = $this->getJson(route('api.v1.media.show', $media));

        $response->assertStatus(401);
    });

    it('requires view_media permission for other users media', function () {
        $user1 = createUserWithRole(UserRole::AUTHOR->value);
        $user2 = User::factory()->create();
        $media = Media::factory()->for($user2, 'uploader')->create();

        $response = $this->actingAs($user1)
            ->getJson(route('api.v1.media.show', $media));

        $response->assertStatus(403);
    });

    it('allows owner to view their own media', function () {
        $user = createUserWithRole(UserRole::AUTHOR->value);
        $media = Media::factory()->for($user, 'uploader')->create();

        $response = $this->actingAs($user)
            ->getJson(route('api.v1.media.show', $media));

        expect($response)->toHaveApiSuccessStructure();
    });
});
