<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User\Notification;

use App\Data\FilterUserNotificationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\Notification\GetUserNotificationsRequest;
use App\Http\Resources\MetaResource;
use App\Http\Resources\V1\User\Notification\UserNotificationResource;
use App\Services\Interfaces\UserNotificationServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('User Notifications', weight: 3)]
final class GetUserNotificationsController extends Controller
{
    public function __construct(
        private readonly UserNotificationServiceInterface $userNotificationService
    ) {}

    /**
     * Get User's Notifications
     *
     * Retrieves a paginated list of notifications for the authenticated user. This endpoint
     * allows users to view all their notifications with comprehensive filtering and sorting
     * capabilities. Notifications can be filtered by read status, type, and date range.
     * Each notification includes the full notification content, read status, and timestamps.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `read_notifications` permission.
     * Users can only view their own notifications.
     *
     * **Query Parameters (all optional):**
     * - `is_read` (boolean): Filter notifications by read status (true for read, false for unread)
     * - `type` (enum: article_published|new_comment|newsletter|system_alert): Filter notifications by notification type
     * - `created_at_from` (date, Y-m-d format): Filter notifications created on or after this date
     * - `created_at_to` (date, Y-m-d format): Filter notifications created on or before this date (must be after or equal to created_at_from)
     * - `sort_by` (enum: created_at|updated_at, default: created_at): Field to sort by
     * - `sort_order` (enum: asc|desc, default: desc): Sort order
     * - `per_page` (integer, min:1, max:100, default: 15): Number of notifications per page
     *
     * **Response:**
     * Returns a paginated collection of the user's notifications with full details including
     * notification content, read status, creation dates, and metadata. Includes pagination
     * metadata with total count, current page, per page limit, and pagination links.
     *
     * **Note:** Notifications are returned with their associated notification details loaded,
     * including message content and type information.
     *
     * @response array{status: true, message: string, data: array{notifications: UserNotificationResource[], meta: MetaResource}}
     */
    public function __invoke(GetUserNotificationsRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            assert($user !== null);
            $dto = FilterUserNotificationDTO::fromRequest($request);
            $notifications = $this->userNotificationService->getUserNotifications($user, $dto);

            $notificationCollection = UserNotificationResource::collection($notifications);
            $notificationCollectionData = $notificationCollection->response()->getData(true);

            // Ensure we have the expected array structure
            if (! is_array($notificationCollectionData) || ! isset($notificationCollectionData['data'], $notificationCollectionData['meta'])) {
                throw new \RuntimeException(__('common.unexpected_response_format'));
            }

            return response()->apiSuccess(
                [
                    'notifications' => $notificationCollectionData['data'],
                    'meta' => MetaResource::make($notificationCollectionData['meta']),
                ],
                __('common.success')
            );
        } catch (Throwable $e) {
            /**
             * Internal server error
             *
             * @status 500
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return $this->handleException($e, $request);
        }
    }
}
