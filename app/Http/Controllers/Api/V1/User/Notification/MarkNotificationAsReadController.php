<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\Notification\MarkNotificationAsReadRequest;
use App\Http\Resources\V1\User\Notification\UserNotificationResource;
use App\Models\UserNotification;
use App\Services\Interfaces\UserNotificationServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('User Notifications', weight: 3)]
final class MarkNotificationAsReadController extends Controller
{
    public function __construct(
        private readonly UserNotificationServiceInterface $userNotificationService
    ) {}

    /**
     * Mark Notification as Read
     *
     * Marks a specific notification as read for the authenticated user. This endpoint
     * allows users to update the read status of their notifications. Users can only
     * mark their own notifications as read. The notification's `is_read` field is set
     * to true, and the `updated_at` timestamp is refreshed.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `read_notifications` permission.
     * Users can only mark their own notifications as read.
     *
     * **Route Parameters:**
     * - `userNotification` (UserNotification, required): The user notification model instance to mark as read (route model binding)
     *
     * **Response:**
     * Returns the updated notification object with the read status set to true. The response
     * includes HTTP 200 OK status code and the full notification details.
     *
     * **Note:** If the notification is already marked as read, the operation will still succeed
     * and return the notification with its current state. The service layer ensures users can
     * only mark their own notifications as read.
     *
     * @response array{status: true, message: string, data: UserNotificationResource}
     */
    public function __invoke(MarkNotificationAsReadRequest $request, UserNotification $userNotification): JsonResponse
    {
        try {
            $user = $request->user();
            assert($user !== null);
            $notification = $this->userNotificationService->markAsRead($user, $userNotification->id);

            return response()->apiSuccess(
                new UserNotificationResource($notification),
                __('common.notification_marked_as_read')
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
