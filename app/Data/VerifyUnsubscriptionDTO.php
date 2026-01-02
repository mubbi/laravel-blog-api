<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\V1\Newsletter\VerifyUnsubscriptionRequest;

/**
 * Data Transfer Object for verifying newsletter unsubscription
 */
final class VerifyUnsubscriptionDTO
{
    public function __construct(
        public readonly string $token,
        public readonly string $email,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(VerifyUnsubscriptionRequest $request): self
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
