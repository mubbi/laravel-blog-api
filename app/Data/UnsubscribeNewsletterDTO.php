<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\V1\Newsletter\UnsubscribeRequest;

/**
 * Data Transfer Object for unsubscribing from newsletter
 */
final class UnsubscribeNewsletterDTO
{
    public function __construct(
        public readonly string $email,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(UnsubscribeRequest $request): self
    {
        $defaults = $request->withDefaults();

        return new self(
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
            'email' => $this->email,
        ];
    }
}
