<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\Media\MediaUploadedEvent;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

describe('API/V1/Media/UploadMediaController', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('uploads media file with valid data and dispatches event', function () {
        Event::fake([MediaUploadedEvent::class]);
        $user = createUserWithRole(UserRole::AUTHOR->value);
        $file = createFakeImageFile('test-image.jpg', 800, 600);

        $response = $this->actingAs($user)
            ->postJson(route('api.v1.media.store'), [
                'file' => $file,
                'name' => 'Test Image',
                'alt_text' => 'Test alt text',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'name', 'file_name', 'mime_type', 'url', 'type'],
            ])
            ->assertJson([
                'status' => true,
                'data' => ['name' => 'Test Image', 'alt_text' => 'Test alt text', 'type' => 'image'],
            ]);

        $this->assertDatabaseHas('media', [
            'name' => 'Test Image',
            'alt_text' => 'Test alt text',
            'uploaded_by' => $user->id,
            'type' => 'image',
        ]);

        $media = Media::where('name', 'Test Image')->first();
        expect($media)->not->toBeNull();
        Storage::disk('public')->assertExists($media->path);
        Event::assertDispatched(MediaUploadedEvent::class, fn ($event) => $event->media->id === $media->id);
    });

    it('requires authentication', function () {
        $response = $this->postJson(route('api.v1.media.store'), [
            'file' => createFakeImageFile('test-image.jpg'),
        ]);

        $response->assertStatus(401);
    });

    it('requires upload_media permission', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('api.v1.media.store'), [
                'file' => createFakeImageFile('test-image.jpg'),
            ]);

        $response->assertStatus(403);
    });

    it('validates file is required', function () {
        $user = createUserWithRole(UserRole::AUTHOR->value);

        $response = $this->actingAs($user)
            ->postJson(route('api.v1.media.store'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    });

    it('validates file size limit', function () {
        $user = createUserWithRole(UserRole::AUTHOR->value);

        $response = $this->actingAs($user)
            ->postJson(route('api.v1.media.store'), [
                'file' => UploadedFile::fake()->create('large-file.pdf', 11000), // 11MB
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    });
});
