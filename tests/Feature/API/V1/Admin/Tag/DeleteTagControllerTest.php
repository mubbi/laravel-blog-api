<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Tag;

describe('API/V1/Tag/DeleteTagController', function () {
    it('can delete a tag successfully', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $tag = Tag::factory()->create();

        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.tags.destroy', $tag));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('message'))->toBe(__('common.tag_deleted_successfully'));

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    });

    it('requires delete_tags permission', function () {
        $user = createUserWithRole(UserRole::AUTHOR->value);
        $tag = Tag::factory()->create();

        $response = $this->actingAs($user)
            ->deleteJson(route('api.v1.tags.destroy', $tag));

        $response->assertStatus(403);
    });
});
