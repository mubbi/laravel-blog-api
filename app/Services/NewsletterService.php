<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class NewsletterService
{
    /**
     * Delete a newsletter subscriber
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ModelNotFoundException
     */
    public function deleteSubscriber(int $subscriberId, array $data): void
    {
        $subscriber = NewsletterSubscriber::findOrFail($subscriberId);
        $subscriber->delete();
    }

    /**
     * Get subscriber by ID
     *
     * @throws ModelNotFoundException
     */
    public function getSubscriberById(int $subscriberId): NewsletterSubscriber
    {
        return NewsletterSubscriber::findOrFail($subscriberId);
    }

    /**
     * Get subscribers with filters
     *
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, NewsletterSubscriber>
     */
    public function getSubscribers(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = NewsletterSubscriber::query();

        if (isset($filters['search'])) {
            /** @var string $searchTerm */
            $searchTerm = $filters['search'];
            $query->where('email', 'like', "%{$searchTerm}%");
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'verified') {
                $query->where('is_verified', true);
            } elseif ($filters['status'] === 'unverified') {
                $query->where('is_verified', false);
            }
        }

        if (isset($filters['subscribed_at_from'])) {
            $query->where('created_at', '>=', $filters['subscribed_at_from']);
        }

        if (isset($filters['subscribed_at_to'])) {
            $query->where('created_at', '<=', $filters['subscribed_at_to']);
        }

        /** @var string $sortBy */
        $sortBy = $filters['sort_by'] ?? 'created_at';
        /** @var string $sortOrder */
        $sortOrder = $filters['sort_order'] ?? 'desc';
        /** @var int $perPage */
        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
    }

    /**
     * Get total subscriber count
     */
    public function getTotalSubscribers(): int
    {
        return NewsletterSubscriber::count();
    }
}
