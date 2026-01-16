<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\User\FilterUserNotificationDTO;
use App\Models\User;
use App\Models\UserNotification;
use App\Repositories\Contracts\UserNotificationRepositoryInterface;
use App\Services\Interfaces\UserNotificationServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

final class UserNotificationService implements UserNotificationServiceInterface
{
    public function __construct(
        private readonly UserNotificationRepositoryInterface $userNotificationRepository
    ) {}

    /**
     * Get user's notifications with filters
     *
     * @return LengthAwarePaginator<int, UserNotification>
     */
    public function getUserNotifications(User $user, FilterUserNotificationDTO $dto): LengthAwarePaginator
    {
        $query = $this->userNotificationRepository->forUser($user->id)
            ->with(['notification']);

        $this->applyFilters($query, $dto);

        $query->orderBy($dto->sortBy, $dto->sortOrder);

        /** @var LengthAwarePaginator<int, UserNotification> $paginator */
        $paginator = $query->paginate($dto->perPage);

        return $paginator;
    }

    /**
     * Mark a notification as read
     *
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function markAsRead(User $user, int $userNotificationId): UserNotification
    {
        $userNotification = $this->userNotificationRepository->findOrFail($userNotificationId);

        // Ensure the notification belongs to the user
        if ($userNotification->user_id !== $user->id) {
            throw new AuthorizationException(
                __('common.unauthorized')
            );
        }

        $this->userNotificationRepository->markAsRead($userNotificationId);

        // Reload to get updated data
        $userNotification->refresh();

        return $userNotification;
    }

    /**
     * Delete a user notification
     *
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function deleteNotification(User $user, int $userNotificationId): bool
    {
        $userNotification = $this->userNotificationRepository->findOrFail($userNotificationId);

        // Ensure the notification belongs to the user
        if ($userNotification->user_id !== $user->id) {
            throw new AuthorizationException(
                __('common.unauthorized')
            );
        }

        return $this->userNotificationRepository->delete($userNotificationId);
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(User $user): int
    {
        return $this->userNotificationRepository->markAllAsRead($user->id);
    }

    /**
     * Get unread notifications count for a user
     */
    public function getUnreadCount(User $user): int
    {
        return $this->userNotificationRepository->getUnreadCount($user->id);
    }

    /**
     * Apply filters to the query
     *
     * @param  Builder<UserNotification>  $query
     */
    private function applyFilters(Builder $query, FilterUserNotificationDTO $dto): void
    {
        if ($dto->isRead !== null) {
            $query->where('is_read', $dto->isRead);
        }

        if ($dto->type !== null) {
            $query->whereHas('notification', function (Builder $q) use ($dto) {
                $q->where('type', $dto->type->value);
            });
        }

        if ($dto->createdAtFrom !== null) {
            $query->where('created_at', '>=', $dto->createdAtFrom);
        }

        if ($dto->createdAtTo !== null) {
            $query->where('created_at', '<=', $dto->createdAtTo);
        }
    }
}
