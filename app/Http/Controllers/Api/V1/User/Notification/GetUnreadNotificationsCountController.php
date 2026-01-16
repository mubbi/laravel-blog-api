<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\Notification\GetUnreadNotificationsCountRequest;
use App\Services\Interfaces\UserNotificationServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('User Notifications', weight: 3)]
final class GetUnreadNotificationsCountController extends Controller
{
    public function __construct(
        private readonly UserNotificationServiceInterface $userNotificationService
    ) {}

    /**
     * Get Unread Notifications Count
     *
     * Retrieves the count of unread notifications for the authenticated user. This endpoint
     * is useful for displaying notification badges or indicators in the user interface. The
     * count represents the total number of notifications that have not been marked as read.
     * This is a lightweight endpoint optimized for frequent polling or badge updates.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `read_notifications` permission.
     * Users can only get their own unread notifications count.
     *
     * **Response:**
     * Returns the count of unread notifications for the authenticated user. The response
     * includes HTTP 200 OK status code and the unread count in the data field.
     *
     * **Note:** The count is calculated in real-time and reflects the current state of the
     * user's notifications. This endpoint does not return the actual notification data,
     * only the count for performance optimization.
     *
     * @response array{status: true, message: string, data: array{unread_count: int}}
     */
    public function __invoke(GetUnreadNotificationsCountRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            assert($user !== null);
            $unreadCount = $this->userNotificationService->getUnreadCount($user);

            return response()->apiSuccess(
                [
                    'unread_count' => $unreadCount,
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
