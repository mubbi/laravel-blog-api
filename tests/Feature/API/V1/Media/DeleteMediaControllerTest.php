<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\Media\MediaDeletedEvent;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

describe('API/V1/Media/DeleteMediaController', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('deletes media and dispatches event', function () {
        Event::fake([MediaDeletedEvent::class]);
        $user = createUserWithRole(UserRole::AUTHOR->value);
        $media = Media::factory()->for($user, 'uploader')->create();
        $path = $media->path;
        Storage::disk('public')->put($path, 'fake content');

        $response = $this->actingAs($user)
            ->deleteJson(route('api.v1.media.destroy', $media));

        $response->assertStatus(200)
            ->assertJson(['status' => true]);

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($path);

        Event::assertDispatched(MediaDeletedEvent::class, fn ($event) => $event->media->id === $media->id);
    });

    it('requires authentication', function () {
        $media = Media::factory()->create();

        $response = $this->deleteJson(route('api.v1.media.destroy', $media));

        $response->assertStatus(401);
    });

    it('requires manage_media or delete_media permission', function () {
        $user1 = createUserWithRole(UserRole::AUTHOR->value);
        $user2 = User::factory()->create();
        $media = Media::factory()->for($user2, 'uploader')->create();

        $response = $this->actingAs($user1)
            ->deleteJson(route('api.v1.media.destroy', $media));

        $response->assertStatus(403);
    });

    it('allows owner to delete their own media', function () {
        $user = createUserWithRole(UserRole::AUTHOR->value);
        $media = Media::factory()->for($user, 'uploader')->create();

        $response = $this->actingAs($user)
            ->deleteJson(route('api.v1.media.destroy', $media));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    });

    it('returns 404 when media does not exist', function () {
        $user = createUserWithRole(UserRole::AUTHOR->value);

        $response = $this->actingAs($user)
            ->deleteJson(route('api.v1.media.destroy', 99999));

        $response->assertStatus(404);
    });
});
