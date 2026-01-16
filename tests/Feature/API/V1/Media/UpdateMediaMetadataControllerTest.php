<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\Media\MediaMetadataUpdatedEvent;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('API/V1/Media/UpdateMediaMetadataController', function () {
    it('updates media metadata and dispatches event', function () {
        Event::fake([MediaMetadataUpdatedEvent::class]);
        $user = createUserWithRole(UserRole::AUTHOR->value);
        $media = Media::factory()->for($user, 'uploader')->create([
            'name' => 'Old Name',
            'alt_text' => 'Old alt text',
        ]);

        $response = $this->actingAs($user)
            ->putJson(route('api.v1.media.update', $media), [
                'name' => 'New Name',
                'alt_text' => 'New alt text',
                'caption' => 'New caption',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'name', 'alt_text', 'caption'],
            ])
            ->assertJson([
                'status' => true,
                'data' => [
                    'name' => 'New Name',
                    'alt_text' => 'New alt text',
                    'caption' => 'New caption',
                ],
            ]);

        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'name' => 'New Name',
            'alt_text' => 'New alt text',
        ]);

        Event::assertDispatched(MediaMetadataUpdatedEvent::class, fn ($event) => $event->media->id === $media->id);
    });

    it('requires authentication', function () {
        $media = Media::factory()->create();

        $response = $this->putJson(route('api.v1.media.update', $media), [
            'name' => 'New Name',
        ]);

        $response->assertStatus(401);
    });

    it('requires manage_media or edit_media permission', function () {
        $user1 = createUserWithRole(UserRole::AUTHOR->value);
        $user2 = User::factory()->create();
        $media = Media::factory()->for($user2, 'uploader')->create();

        $response = $this->actingAs($user1)
            ->putJson(route('api.v1.media.update', $media), [
                'name' => 'New Name',
            ]);

        $response->assertStatus(403);
    });

    it('allows owner to update their own media', function () {
        $user = createUserWithRole(UserRole::AUTHOR->value);
        $media = Media::factory()->for($user, 'uploader')->create();

        $response = $this->actingAs($user)
            ->putJson(route('api.v1.media.update', $media), [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200);
    });

    it('validates name max length', function () {
        $user = createUserWithRole(UserRole::AUTHOR->value);
        $media = Media::factory()->for($user, 'uploader')->create();

        $response = $this->actingAs($user)
            ->putJson(route('api.v1.media.update', $media), [
                'name' => str_repeat('a', 256),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });
});
