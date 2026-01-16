<?php

declare(strict_types=1);

namespace App\Data\Newsletter;

use App\Http\Requests\V1\Newsletter\VerifySubscriptionRequest;

/**
 * Data Transfer Object for verifying newsletter subscription
 */
final class VerifySubscriptionDTO
{
    public function __construct(
        public readonly string $token,
        public readonly string $email,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(VerifySubscriptionRequest $request): self
    {
        $defaults = $request->withDefaults();

        return new self(
            token: (string) $defaults['token'],
            email: (string) $defaults['email'],
        );
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'email' => $this->email,
        ];
    }
}
