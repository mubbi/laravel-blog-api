<?php

declare(strict_types=1);

namespace App\Data\Article;

use App\Http\Requests\V1\Article\ReportArticleRequest;

/**
 * Data Transfer Object for reporting an article
 */
final class ReportArticleDTO
{
    public function __construct(
        public readonly ?string $reason = null,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(ReportArticleRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            reason: isset($validated['reason']) ? (string) $validated['reason'] : null,
        );
    }

    /**
     * Get the reason for reporting, with fallback
     */
    public function getReason(): string
    {
        return $this->reason ?? __('common.no_reason_provided');
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'reason' => $this->getReason(),
        ];
    }
}
