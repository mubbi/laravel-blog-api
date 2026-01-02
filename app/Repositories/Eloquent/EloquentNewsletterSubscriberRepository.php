<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\NewsletterSubscriber;
use App\Repositories\Contracts\NewsletterSubscriberRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

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
        return $this->query()->count();
    }

    /**
     * Find a newsletter subscriber by email
     */
    public function findByEmail(string $email): ?NewsletterSubscriber
    {
        /** @var NewsletterSubscriber|null $subscriber */
        $subscriber = $this->query()->where('email', $email)->first();

        return $subscriber;
    }

    /**
     * Find a newsletter subscriber by verification token
     * The token parameter should be the plain token, which will be hashed for comparison
     *
     * Note: Since tokens are hashed, we filter by non-null tokens first, then verify the hash.
     * This is more efficient than loading all records.
     */
    public function findByVerificationToken(string $token): ?NewsletterSubscriber
    {
        // Filter by non-null tokens first to reduce the dataset
        $subscribers = $this->query()
            ->whereNotNull('verification_token')
            ->get();

        /** @var NewsletterSubscriber|null $subscriber */
        $subscriber = $subscribers->first(function ($subscriber) use ($token) {
            $verificationToken = $subscriber->verification_token;
            if ($verificationToken === null) {
                return false;
            }

            return Hash::check($token, $verificationToken);
        });

        return $subscriber;
    }

    /**
     * Find a newsletter subscriber by verification token and email
     * The token parameter should be the plain token, which will be hashed for comparison
     *
     * Note: Since tokens are hashed, we filter by email first (indexed), then verify the hash.
     * This is much more efficient than loading all records.
     */
    public function findByVerificationTokenAndEmail(string $token, string $email): ?NewsletterSubscriber
    {
        // Filter by email first (indexed column) and non-null token
        $subscriber = $this->query()
            ->where('email', $email)
            ->whereNotNull('verification_token')
            ->first();

        if ($subscriber === null) {
            return null;
        }

        // Verify the token hash
        $verificationToken = $subscriber->verification_token;
        if ($verificationToken === null || ! Hash::check($token, $verificationToken)) {
            return null;
        }

        return $subscriber;
    }
}
