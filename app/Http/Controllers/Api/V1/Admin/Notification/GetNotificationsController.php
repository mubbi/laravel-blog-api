<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Notification;

use App\Data\FilterNotificationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Notification\GetNotificationsRequest;
use App\Http\Resources\MetaResource;
use App\Http\Resources\V1\Notification\NotificationResource;
use App\Services\NotificationService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - Notifications', weight: 4)]
final class GetNotificationsController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Get Paginated List of Notifications (Admin)
     *
     * Retrieves a paginated list of all system notifications with filtering and sorting
     * capabilities. This endpoint is used for viewing notification history, monitoring
     * notification delivery, and managing system-wide messaging. Includes notifications
     * sent to all users, specific user groups, or individual users.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `view_notifications` permission.
     *
     * **Query Parameters (all optional):**
     * - `per_page` (integer, min:1, max:100, default: 15): Number of notifications per page
     * - `search` (string, max:255): Search term to filter notifications by content
     * - `type` (enum): Filter notifications by notification type
     * - `status` (enum: verified|unverified): Filter notifications by verification/delivery status
     * - `created_at_from` (date, Y-m-d format): Filter notifications created on or after this date
     * - `created_at_to` (date, Y-m-d format): Filter notifications created on or before this date (must be after or equal to created_at_from)
     * - `sort_by` (enum: created_at|updated_at|type, default: created_at): Field to sort by
     * - `sort_order` (enum: asc|desc, default: desc): Sort order
     *
     * **Response:**
     * Returns a paginated collection of notifications with their type, message content,
     * target audiences, delivery status, creation dates, and metadata. Includes pagination
     * metadata with total count, current page, per page limit, and pagination links.
     *
     * @response array{status: true, message: string, data: array{notifications: NotificationResource[], meta: MetaResource}}
     */
    public function __invoke(GetNotificationsRequest $request): JsonResponse
    {
        try {
            $dto = FilterNotificationDTO::fromRequest($request);
            $notifications = $this->notificationService->getNotifications($dto);

            $notificationCollection = NotificationResource::collection($notifications);
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
