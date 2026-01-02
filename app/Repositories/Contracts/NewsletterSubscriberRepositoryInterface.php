<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Newsletter subscriber repository interface
 */
interface NewsletterSubscriberRepositoryInterface
{
    /**
     * Create a new newsletter subscriber
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): NewsletterSubscriber;

    /**
     * Update an existing newsletter subscriber
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    /**
     * Find a newsletter subscriber by ID
     */
    public function findById(int $id): ?NewsletterSubscriber;

    /**
     * Find a newsletter subscriber by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): NewsletterSubscriber;

    /**
     * Delete a newsletter subscriber
     */
    public function delete(int $id): bool;

    /**
     * Get a query builder instance
     *
     * @return Builder<NewsletterSubscriber>
     */
    public function query(): Builder;

    /**
     * Get newsletter subscribers with pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, NewsletterSubscriber>
     */
    public function paginate(array $params): LengthAwarePaginator;

    /**
     * Count all newsletter subscribers
     */
    public function count(): int;

    /**
     * Find a newsletter subscriber by email
     */
    public function findByEmail(string $email): ?NewsletterSubscriber;

    /**
     * Find a newsletter subscriber by verification token
     */
    public function findByVerificationToken(string $token): ?NewsletterSubscriber;

    /**
     * Find a newsletter subscriber by verification token and email
     */
    public function findByVerificationTokenAndEmail(string $token, string $email): ?NewsletterSubscriber;
}
