<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\V1\Admin\User\UpdateUserRequest;

/**
 * Data Transfer Object for updating a user
 */
final class UpdateUserDTO
{
    /**
     * Track which optional fields were explicitly provided (even if null)
     * This allows us to distinguish between "not provided" and "set to null to clear"
     *
     * @var array<string>
     */
    private array $providedFields = [];

    /**
     * @param  array<int>|null  $roleIds
     * @param  array<string>|null  $providedFields  List of field names that were explicitly provided
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $password = null,
        public readonly ?string $avatarUrl = null,
        public readonly ?string $bio = null,
        public readonly ?string $twitter = null,
        public readonly ?string $facebook = null,
        public readonly ?string $linkedin = null,
        public readonly ?string $github = null,
        public readonly ?string $website = null,
        public readonly ?array $roleIds = null,
        ?array $providedFields = null,
    ) {
        $this->providedFields = $providedFields ?? [];
    }

    /**
     * Create DTO from request
     */
    public static function fromRequest(UpdateUserRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: isset($validated['name']) ? (string) $validated['name'] : null,
            email: isset($validated['email']) ? (string) $validated['email'] : null,
            password: isset($validated['password']) ? (string) $validated['password'] : null,
            avatarUrl: isset($validated['avatar_url']) ? (string) $validated['avatar_url'] : null,
            bio: isset($validated['bio']) ? (string) $validated['bio'] : null,
            twitter: isset($validated['twitter']) ? (string) $validated['twitter'] : null,
            facebook: isset($validated['facebook']) ? (string) $validated['facebook'] : null,
            linkedin: isset($validated['linkedin']) ? (string) $validated['linkedin'] : null,
            github: isset($validated['github']) ? (string) $validated['github'] : null,
            website: isset($validated['website']) ? (string) $validated['website'] : null,
            roleIds: isset($validated['role_ids']) && is_array($validated['role_ids']) ? array_map(fn ($id) => (int) $id, $validated['role_ids']) : null,
        );
    }

    /**
     * Create DTO from array
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: isset($data['name']) ? (string) $data['name'] : null,
            email: isset($data['email']) ? (string) $data['email'] : null,
            password: isset($data['password']) ? (string) $data['password'] : null,
            avatarUrl: isset($data['avatar_url']) ? (string) $data['avatar_url'] : null,
            bio: isset($data['bio']) ? (string) $data['bio'] : null,
            twitter: isset($data['twitter']) ? (string) $data['twitter'] : null,
            facebook: isset($data['facebook']) ? (string) $data['facebook'] : null,
            linkedin: isset($data['linkedin']) ? (string) $data['linkedin'] : null,
            github: isset($data['github']) ? (string) $data['github'] : null,
            website: isset($data['website']) ? (string) $data['website'] : null,
            roleIds: isset($data['role_ids']) && is_array($data['role_ids']) ? array_map(fn ($id) => (int) $id, $data['role_ids']) : null,
        );
    }

    /**
     * Convert to array for database operations
     * Includes null values for optional clearable fields that were explicitly provided
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        // Required fields - only include if not null
        if ($this->name !== null) {
            $data['name'] = $this->name;
        }
        if ($this->email !== null) {
            $data['email'] = $this->email;
        }
        if ($this->password !== null) {
            $data['password'] = $this->password;
        }

        // Optional clearable fields - include if explicitly provided (even if null) or if not null
        $clearableFieldsMap = [
            'avatar_url' => 'avatarUrl',
            'bio' => 'bio',
            'twitter' => 'twitter',
            'facebook' => 'facebook',
            'linkedin' => 'linkedin',
            'github' => 'github',
            'website' => 'website',
        ];

        foreach ($clearableFieldsMap as $field => $property) {
            $value = $this->{$property};

            // Include if explicitly provided (even if null) or if value is not null
            if (in_array($field, $this->providedFields, true) || $value !== null) {
                $data[$field] = $value;
            }
        }

        return $data;
    }
}
