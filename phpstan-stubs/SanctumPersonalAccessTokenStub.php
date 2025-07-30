<?php

namespace Laravel\Sanctum;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read User|null $tokenable
 * @property-read \Illuminate\Support\Carbon|null $expires_at
 */
class PersonalAccessToken extends Model
{
    /**
     * Find the token instance matching the given token.
     */
    public static function findToken(string $token): ?self
    {
        return new self;
    }

    /**
     * Check if the token has a given ability.
     */
    public function can(string $ability): bool
    {
        return true;
    }

    /**
     * Get the tokenable model that the access token belongs to.
     */
    public function tokenable(): MorphTo
    {
        return new MorphTo;
    }

    /**
     * Delete the token.
     */
    public function delete(): bool
    {
        return true;
    }
}
