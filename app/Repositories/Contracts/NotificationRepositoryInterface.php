<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Notification repository interface
 */
interface NotificationRepositoryInterface
{
    /**
     * Create a new notification
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Notification;

    /**
     * Update an existing notification
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    /**
     * Find a notification by ID
     */
    public function findById(int $id): ?Notification;

    /**
     * Find a notification by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Notification;

    /**
     * Delete a notification
     */
    public function delete(int $id): bool;

    /**
     * Get a query builder instance
     *
     * @return Builder<Notification>
     */
    public function query(): Builder;

    /**
     * Get notifications with pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, Notification>
     */
    public function paginate(array $params): LengthAwarePaginator;

    /**
     * Count all notifications
     */
    public function count(): int;

    /**
     * Count notifications by type
     */
    public function countByType(string $type): int;
}
