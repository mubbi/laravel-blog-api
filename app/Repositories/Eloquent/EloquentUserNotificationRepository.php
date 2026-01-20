<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\UserNotification;
use App\Repositories\Contracts\UserNotificationRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eloquent implementation of UserNotificationRepositoryInterface
 *
 * @extends BaseEloquentRepository<UserNotification>
 */
final class EloquentUserNotificationRepository extends BaseEloquentRepository implements UserNotificationRepositoryInterface
{
    /**
     * Get the model class name
     *
     * @return class-string<UserNotification>
     */
    protected function getModelClass(): string
    {
        return UserNotification::class;
    }

    /**
     * Create a new user notification
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): UserNotification
    {
        /** @var UserNotification $userNotification */
        $userNotification = parent::create($data);

        return $userNotification;
    }

    /**
     * Update an existing user notification
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool
    {
        return parent::update($id, $data);
    }

    /**
     * Find a user notification by ID
     */
    public function findById(int $id): ?UserNotification
    {
        /** @var UserNotification|null $userNotification */
        $userNotification = parent::findById($id);

        return $userNotification;
    }

    /**
     * Find a user notification by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): UserNotification
    {
        /** @var UserNotification $userNotification */
        $userNotification = parent::findOrFail($id);

        return $userNotification;
    }

    /**
     * Delete a user notification
     */
    public function delete(int $id): bool
    {
        return parent::delete($id);
    }

    /**
     * Get a query builder instance
     *
     * @return Builder<UserNotification>
     */
    public function query(): Builder
    {
        /** @var Builder<UserNotification> $builder */
        $builder = parent::query();

        return $builder;
    }

    /**
     * Get user notifications for a specific user
     *
     * @return Builder<UserNotification>
     */
    public function forUser(int $userId): Builder
    {
        return $this->query()->where('user_id', $userId);
    }

    /**
     * Get unread notifications count for a user
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->forUser($userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $id): bool
    {
        return $this->update($id, ['is_read' => true]);
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(int $userId): int
    {
        return $this->forUser($userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * Get user notifications with pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, UserNotification>
     */
    public function paginate(array $params): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, UserNotification> $paginator */
        $paginator = parent::paginate($params);

        return $paginator;
    }
}
