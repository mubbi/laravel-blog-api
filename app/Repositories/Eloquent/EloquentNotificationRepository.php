<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Notification;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eloquent implementation of NotificationRepositoryInterface
 *
 * @extends BaseEloquentRepository<Notification>
 */
final class EloquentNotificationRepository extends BaseEloquentRepository implements NotificationRepositoryInterface
{
    /**
     * Get the model class name
     *
     * @return class-string<Notification>
     */
    protected function getModelClass(): string
    {
        return Notification::class;
    }

    /**
     * Create a new notification
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Notification
    {
        /** @var Notification $notification */
        $notification = parent::create($data);

        return $notification;
    }

    /**
     * Find a notification by ID
     */
    public function findById(int $id): ?Notification
    {
        /** @var Notification|null $notification */
        $notification = parent::findById($id);

        return $notification;
    }

    /**
     * Find a notification by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Notification
    {
        /** @var Notification $notification */
        $notification = parent::findOrFail($id);

        return $notification;
    }

    /**
     * Get a query builder instance
     *
     * @return Builder<Notification>
     */
    public function query(): Builder
    {
        /** @var Builder<Notification> $builder */
        $builder = parent::query();

        return $builder;
    }

    /**
     * Get notifications with pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, Notification>
     */
    public function paginate(array $params): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, Notification> $paginator */
        $paginator = parent::paginate($params);

        return $paginator;
    }

    /**
     * Count all notifications
     */
    public function count(): int
    {
        return $this->query()->count();
    }

    /**
     * Count notifications by type
     */
    public function countByType(string $type): int
    {
        return $this->query()->where('type', $type)->count();
    }
}
