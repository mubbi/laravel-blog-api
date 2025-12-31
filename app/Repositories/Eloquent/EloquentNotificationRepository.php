<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Notification;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eloquent implementation of NotificationRepositoryInterface
 */
final class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    /**
     * Create a new notification
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    /**
     * Update an existing notification
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool
    {
        $notification = $this->findOrFail($id);

        return $notification->update($data);
    }

    /**
     * Find a notification by ID
     */
    public function findById(int $id): ?Notification
    {
        return Notification::find($id);
    }

    /**
     * Find a notification by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Notification
    {
        return Notification::findOrFail($id);
    }

    /**
     * Delete a notification
     */
    public function delete(int $id): bool
    {
        $notification = $this->findOrFail($id);

        /** @var bool $result */
        $result = $notification->delete();

        return $result;
    }

    /**
     * Get a query builder instance
     *
     * @return Builder<Notification>
     */
    public function query(): Builder
    {
        return Notification::query();
    }

    /**
     * Get notifications with pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, Notification>
     */
    public function paginate(array $params): LengthAwarePaginator
    {
        $perPage = $params['per_page'] ?? 15;
        $page = $params['page'] ?? 1;

        return $this->query()->paginate((int) $perPage, ['*'], 'page', (int) $page);
    }

    /**
     * Count all notifications
     */
    public function count(): int
    {
        return Notification::count();
    }

    /**
     * Count notifications by type
     */
    public function countByType(string $type): int
    {
        return Notification::where('type', $type)->count();
    }
}
