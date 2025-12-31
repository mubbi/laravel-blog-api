<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\FilterNewsletterSubscriberDTO;
use App\Events\Newsletter\NewsletterSubscriberDeletedEvent;
use App\Models\NewsletterSubscriber;
use App\Repositories\Contracts\NewsletterSubscriberRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Event;

final class NewsletterService
{
    public function __construct(
        private readonly NewsletterSubscriberRepositoryInterface $newsletterSubscriberRepository
    ) {}

    /**
     * Delete a newsletter subscriber
     *
     * @throws ModelNotFoundException
     */
    public function deleteSubscriber(int $subscriberId): void
    {
        $subscriber = $this->newsletterSubscriberRepository->findOrFail($subscriberId);
        $email = $subscriber->email;
        $this->newsletterSubscriberRepository->delete($subscriberId);

        Event::dispatch(new NewsletterSubscriberDeletedEvent($subscriberId, $email));
    }

    /**
     * Get subscriber by ID
     *
     * @throws ModelNotFoundException
     */
    public function getSubscriberById(int $subscriberId): NewsletterSubscriber
    {
        return $this->newsletterSubscriberRepository->findOrFail($subscriberId);
    }

    /**
     * Get subscribers with filters
     *
     * @return LengthAwarePaginator<int, NewsletterSubscriber>
     */
    public function getSubscribers(FilterNewsletterSubscriberDTO $dto): LengthAwarePaginator
    {
        $query = $this->newsletterSubscriberRepository->query();

        if ($dto->search !== null) {
            $query->where('email', 'like', "%{$dto->search}%");
        }

        if ($dto->status !== null) {
            if ($dto->status === 'verified') {
                $query->where('is_verified', true);
            } elseif ($dto->status === 'unverified') {
                $query->where('is_verified', false);
            }
        }

        if ($dto->subscribedAtFrom !== null) {
            $query->where('created_at', '>=', $dto->subscribedAtFrom);
        }

        if ($dto->subscribedAtTo !== null) {
            $query->where('created_at', '<=', $dto->subscribedAtTo);
        }

        return $query->orderBy($dto->sortBy, $dto->sortOrder)->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }

    /**
     * Get total subscriber count
     */
    public function getTotalSubscribers(): int
    {
        return $this->newsletterSubscriberRepository->count();
    }
}
