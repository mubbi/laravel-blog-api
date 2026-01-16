<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('API/V1/User/MeController', function () {
    it('returns authenticated user profile with roles and permissions', function () {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        Sanctum::actingAs($user, ['access-api']);

        $response = $this->getJson(route('api.v1.me'));

        expect($response)->toHaveApiSuccessStructure([
            'id',
            'name',
            'email',
            'email_verified_at',
            'bio',
            'avatar_url',
            'twitter',
            'facebook',
            'linkedin',
            'github',
            'website',
        ])->and($response->json('data.id'))->toBe($user->id)
            ->and($response->json('data.name'))->toBe('John Doe')
            ->and($response->json('data.email'))->toBe('john@example.com');
    });

    it('returns 401 when not authenticated', function () {
        $response = $this->getJson(route('api.v1.me'));

        $response->assertStatus(401);
    });

    it('returns 401 when token lacks access-api ability', function () {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        Sanctum::actingAs($user, ['read']);

        $response = $this->getJson(route('api.v1.me'));

        $response->assertStatus(401);
    });

    it('handles user with complete profile information', function () {
        $user = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'bio' => 'Software developer and tech enthusiast',
            'avatar_url' => 'https://example.com/avatar.jpg',
            'twitter' => 'https://twitter.com/janesmith',
            'facebook' => 'https://facebook.com/janesmith',
            'linkedin' => 'https://linkedin.com/in/janesmith',
            'github' => 'https://github.com/janesmith',
            'website' => 'https://janesmith.dev',
        ]);

        Sanctum::actingAs($user, ['access-api']);

        $response = $this->getJson(route('api.v1.me'));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.id'))->toBe($user->id)
            ->and($response->json('data.name'))->toBe('Jane Smith')
            ->and($response->json('data.email'))->toBe('jane@example.com')
            ->and($response->json('data.bio'))->toBe('Software developer and tech enthusiast');
    });

    it('handles user with minimal profile information', function () {
        $user = User::factory()->create([
            'name' => 'Minimal User',
            'email' => 'minimal@example.com',
            'bio' => null,
            'avatar_url' => null,
            'twitter' => null,
            'facebook' => null,
            'linkedin' => null,
            'github' => null,
            'website' => null,
        ]);

        Sanctum::actingAs($user, ['access-api']);

        $response = $this->getJson(route('api.v1.me'));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.id'))->toBe($user->id)
            ->and($response->json('data.name'))->toBe('Minimal User')
            ->and($response->json('data.email'))->toBe('minimal@example.com')
            ->and($response->json('data.bio'))->toBeNull();
    });

    it('handles user with verified email', function () {
        $user = User::factory()->create([
            'name' => 'Verified User',
            'email' => 'verified@example.com',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['access-api']);

        $response = $this->getJson(route('api.v1.me'));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.id'))->toBe($user->id)
            ->and($response->json('data.email'))->toBe('verified@example.com')
            ->and($response->json('data.email_verified_at'))->not->toBeNull();
    });

    it('handles user with unverified email', function () {
        $user = User::factory()->create([
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
        ]);

        Sanctum::actingAs($user, ['access-api']);

        $response = $this->getJson(route('api.v1.me'));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.id'))->toBe($user->id)
            ->and($response->json('data.email'))->toBe('unverified@example.com')
            ->and($response->json('data.email_verified_at'))->toBeNull();
    });

    it('handles user with long bio text', function () {
        $longBio = str_repeat('This is a very long bio text. ', 20);
        $user = User::factory()->create([
            'name' => 'Long Bio User',
            'email' => 'longbio@example.com',
            'bio' => $longBio,
        ]);

        Sanctum::actingAs($user, ['access-api']);

        $response = $this->getJson(route('api.v1.me'));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.id'))->toBe($user->id)
            ->and($response->json('data.bio'))->toBe($longBio);
    });

    it('handles user with special characters in name', function () {
        $user = User::factory()->create([
            'name' => 'José María O\'Connor-Smith',
            'email' => 'special@example.com',
        ]);

        Sanctum::actingAs($user, ['access-api']);

        $response = $this->getJson(route('api.v1.me'));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.id'))->toBe($user->id)
            ->and($response->json('data.name'))->toBe('José María O\'Connor-Smith');
    });

    it('handles user with very long email address', function () {
        $longEmail = 'very.long.email.address.with.many.subdomains@very.long.domain.name.example.com';
        $user = User::factory()->create(['email' => $longEmail]);

        Sanctum::actingAs($user, ['access-api']);

        $response = $this->getJson(route('api.v1.me'));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.id'))->toBe($user->id)
            ->and($response->json('data.email'))->toBe($longEmail);
    });

    it('handles user with roles and permissions', function () {
        $user = createUserWithRole(\App\Enums\UserRole::ADMINISTRATOR->value);

        Sanctum::actingAs($user, ['access-api']);

        $response = $this->getJson(route('api.v1.me'));

        expect($response)->toHaveApiSuccessStructure([
            'id',
            'name',
            'email',
            'email_verified_at',
            'bio',
            'avatar_url',
            'twitter',
            'facebook',
            'linkedin',
            'github',
            'website',
        ])->and($response->json('data.id'))->toBe($user->id);
    });

    it('handles user with banned status', function () {
        $user = User::factory()->create([
            'name' => 'Banned User',
            'email' => 'banned@example.com',
            'banned_at' => now(),
        ]);

        Sanctum::actingAs($user, ['access-api']);

        $response = $this->getJson(route('api.v1.me'));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.id'))->toBe($user->id)
            ->and($response->json('data.name'))->toBe('Banned User');
    });

    it('handles user with blocked status', function () {
        $user = User::factory()->create([
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'blocked_at' => now(),
        ]);

        Sanctum::actingAs($user, ['access-api']);

        $response = $this->getJson(route('api.v1.me'));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.id'))->toBe($user->id)
            ->and($response->json('data.name'))->toBe('Blocked User');
    });
});
