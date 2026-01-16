<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\Media\MediaMetadataUpdatedEvent;
use App\Models\Media;
use App\Models\User;
use Illuminate\Support\Facades\Event;

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

        expect($response)->toHaveApiSuccessStructure([
            'id', 'name', 'alt_text', 'caption',
        ])->and($response->json('data.name'))->toBe('New Name')
            ->and($response->json('data.alt_text'))->toBe('New alt text')
            ->and($response->json('data.caption'))->toBe('New caption');

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

        expect($response)->toHaveApiSuccessStructure();
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
