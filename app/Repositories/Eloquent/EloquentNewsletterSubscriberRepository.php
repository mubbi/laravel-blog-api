<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\NewsletterSubscriber;
use App\Repositories\Contracts\NewsletterSubscriberRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eloquent implementation of NewsletterSubscriberRepositoryInterface
 *
 * @extends BaseEloquentRepository<NewsletterSubscriber>
 */
final class EloquentNewsletterSubscriberRepository extends BaseEloquentRepository implements NewsletterSubscriberRepositoryInterface
{
    /**
     * Get the model class name
     *
     * @return class-string<NewsletterSubscriber>
     */
    protected function getModelClass(): string
    {
        return NewsletterSubscriber::class;
    }

    /**
     * Create a new newsletter subscriber
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): NewsletterSubscriber
    {
        /** @var NewsletterSubscriber $subscriber */
        $subscriber = parent::create($data);

        return $subscriber;
    }

    /**
     * Find a newsletter subscriber by ID
     */
    public function findById(int $id): ?NewsletterSubscriber
    {
        /** @var NewsletterSubscriber|null $subscriber */
        $subscriber = parent::findById($id);

        return $subscriber;
    }

    /**
     * Find a newsletter subscriber by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): NewsletterSubscriber
    {
        /** @var NewsletterSubscriber $subscriber */
        $subscriber = parent::findOrFail($id);

        return $subscriber;
    }

    /**
     * Get a query builder instance
     *
     * @return Builder<NewsletterSubscriber>
     */
    public function query(): Builder
    {
        /** @var Builder<NewsletterSubscriber> $builder */
        $builder = parent::query();

        return $builder;
    }

    /**
     * Get newsletter subscribers with pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, NewsletterSubscriber>
     */
    public function paginate(array $params): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, NewsletterSubscriber> $paginator */
        $paginator = parent::paginate($params);

        return $paginator;
    }

    /**
     * Count all newsletter subscribers
     */
    public function count(): int
    {
        return NewsletterSubscriber::count();
    }
}
