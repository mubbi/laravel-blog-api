<?php

declare(strict_types=1);

namespace App\Data\Newsletter;

use App\Http\Requests\V1\Newsletter\GetSubscribersRequest;

/**
 * Data Transfer Object for filtering newsletter subscribers
 */
final class FilterNewsletterSubscriberDTO
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $status = null,
        public readonly ?string $subscribedAtFrom = null,
        public readonly ?string $subscribedAtTo = null,
        public readonly string $sortBy = 'created_at',
        public readonly string $sortOrder = 'desc',
        public readonly int $page = 1,
        public readonly int $perPage = 15,
    ) {}

    /**
     * Create DTO from request
     */
    public static function fromRequest(GetSubscribersRequest $request): self
    {
        $validated = $request->validated();
        $defaults = $request->withDefaults();

        return new self(
            search: isset($validated['search']) ? (string) $validated['search'] : null,
            status: isset($validated['status']) ? (string) $validated['status'] : null,
            subscribedAtFrom: isset($validated['subscribed_at_from']) ? (string) $validated['subscribed_at_from'] : null,
            subscribedAtTo: isset($validated['subscribed_at_to']) ? (string) $validated['subscribed_at_to'] : null,
            sortBy: isset($defaults['sort_by']) ? (string) $defaults['sort_by'] : 'created_at',
            sortOrder: isset($defaults['sort_order']) ? (string) $defaults['sort_order'] : 'desc',
            page: isset($defaults['page']) ? (int) $defaults['page'] : 1,
            perPage: isset($defaults['per_page']) ? (int) $defaults['per_page'] : 15,
        );
    }

    /**
     * Convert to array for database operations
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->search !== null) {
            $data['search'] = $this->search;
        }

        if ($this->status !== null) {
            $data['status'] = $this->status;
        }

        if ($this->subscribedAtFrom !== null) {
            $data['subscribed_at_from'] = $this->subscribedAtFrom;
        }

        if ($this->subscribedAtTo !== null) {
            $data['subscribed_at_to'] = $this->subscribedAtTo;
        }

        $data['sort_by'] = $this->sortBy;
        $data['sort_order'] = $this->sortOrder;
        $data['page'] = $this->page;
        $data['per_page'] = $this->perPage;

        return $data;
    }
}
