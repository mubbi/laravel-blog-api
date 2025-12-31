<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\NewsletterSubscriber;
use App\Repositories\Contracts\NewsletterSubscriberRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eloquent implementation of NewsletterSubscriberRepositoryInterface
 */
final class EloquentNewsletterSubscriberRepository implements NewsletterSubscriberRepositoryInterface
{
    /**
     * Create a new newsletter subscriber
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): NewsletterSubscriber
    {
        return NewsletterSubscriber::create($data);
    }

    /**
     * Update an existing newsletter subscriber
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool
    {
        $subscriber = $this->findOrFail($id);

        return $subscriber->update($data);
    }

    /**
     * Find a newsletter subscriber by ID
     */
    public function findById(int $id): ?NewsletterSubscriber
    {
        return NewsletterSubscriber::find($id);
    }

    /**
     * Find a newsletter subscriber by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): NewsletterSubscriber
    {
        return NewsletterSubscriber::findOrFail($id);
    }

    /**
     * Delete a newsletter subscriber
     */
    public function delete(int $id): bool
    {
        $subscriber = $this->findOrFail($id);

        /** @var bool $result */
        $result = $subscriber->delete();

        return $result;
    }

    /**
     * Get a query builder instance
     *
     * @return Builder<NewsletterSubscriber>
     */
    public function query(): Builder
    {
        return NewsletterSubscriber::query();
    }

    /**
     * Get newsletter subscribers with pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, NewsletterSubscriber>
     */
    public function paginate(array $params): LengthAwarePaginator
    {
        $perPage = $params['per_page'] ?? 15;
        $page = $params['page'] ?? 1;

        return $this->query()->paginate((int) $perPage, ['*'], 'page', (int) $page);
    }

    /**
     * Count all newsletter subscribers
     */
    public function count(): int
    {
        return NewsletterSubscriber::count();
    }
}
