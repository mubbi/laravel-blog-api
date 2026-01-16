<?php

declare(strict_types=1);

namespace App\Data\Newsletter;

use App\Http\Requests\V1\Newsletter\SubscribeRequest;

/**
 * Data Transfer Object for subscribing to newsletter
 */
final class SubscribeNewsletterDTO
{
    public function __construct(
        public readonly string $email,
        public readonly ?int $userId = null,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(SubscribeRequest $request): self
    {
        $defaults = $request->withDefaults();

        return new self(
            email: (string) $defaults['email'],
            userId: isset($defaults['user_id']) ? (int) $defaults['user_id'] : null,
        );
    }

    /**
     * Convert to array for database operations
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'email' => $this->email,
        ];

        if ($this->userId !== null) {
            $data['user_id'] = $this->userId;
        }

        return $data;
    }
}
