<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\Notification\MarkAllNotificationsAsReadRequest;
use App\Services\Interfaces\UserNotificationServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('User Notifications', weight: 3)]
final class MarkAllNotificationsAsReadController extends Controller
{
    public function __construct(
        private readonly UserNotificationServiceInterface $userNotificationService
    ) {}

    /**
     * Mark All Notifications as Read
     *
     * Marks all unread notifications as read for the authenticated user. This endpoint
     * allows users to quickly mark all their unread notifications as read in a single operation.
     * Only unread notifications are affected by this operation. This is useful for bulk
     * notification management and clearing notification badges.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `read_notifications` permission.
     * Users can only mark their own notifications as read.
     *
     * **Response:**
     * Returns a success message with the number of notifications that were marked as read.
     * The response includes HTTP 200 OK status code and the count of notifications updated.
     *
     * **Note:** If there are no unread notifications, the operation will still succeed and
     * return 0 in the `marked_count` field. The operation is idempotent and safe to call
     * multiple times.
     *
     * @response array{status: true, message: string, data: array{marked_count: int}}
     */
    public function __invoke(MarkAllNotificationsAsReadRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            assert($user !== null);
            $markedCount = $this->userNotificationService->markAllAsRead($user);

            return response()->apiSuccess(
                [
                    'marked_count' => $markedCount,
                ],
                __('common.all_notifications_marked_as_read')
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
