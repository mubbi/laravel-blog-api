<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\V1\Admin\User\CreateUserRequest;

/**
 * Data Transfer Object for creating a user
 */
final class CreateUserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $avatarUrl = null,
        public readonly ?string $bio = null,
        public readonly ?string $twitter = null,
        public readonly ?string $facebook = null,
        public readonly ?string $linkedin = null,
        public readonly ?string $github = null,
        public readonly ?string $website = null,
        public readonly ?int $roleId = null,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(CreateUserRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: (string) $validated['name'],
            email: (string) $validated['email'],
            password: (string) $validated['password'],
            avatarUrl: isset($validated['avatar_url']) ? (string) $validated['avatar_url'] : null,
            bio: isset($validated['bio']) ? (string) $validated['bio'] : null,
            twitter: isset($validated['twitter']) ? (string) $validated['twitter'] : null,
            facebook: isset($validated['facebook']) ? (string) $validated['facebook'] : null,
            linkedin: isset($validated['linkedin']) ? (string) $validated['linkedin'] : null,
            github: isset($validated['github']) ? (string) $validated['github'] : null,
            website: isset($validated['website']) ? (string) $validated['website'] : null,
            roleId: isset($validated['role_id']) ? (int) $validated['role_id'] : null,
        );
    }

    /**
     * Convert to array for database operations
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'avatar_url' => $this->avatarUrl,
            'bio' => $this->bio,
            'twitter' => $this->twitter,
            'facebook' => $this->facebook,
            'linkedin' => $this->linkedin,
            'github' => $this->github,
            'website' => $this->website,
        ];
    }
}
