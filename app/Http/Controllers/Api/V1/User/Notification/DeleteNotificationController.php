<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\Notification\DeleteNotificationRequest;
use App\Models\UserNotification;
use App\Services\Interfaces\UserNotificationServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('User Notifications', weight: 3)]
final class DeleteNotificationController extends Controller
{
    public function __construct(
        private readonly UserNotificationServiceInterface $userNotificationService
    ) {}

    /**
     * Delete Notification
     *
     * Permanently deletes a notification for the authenticated user. This endpoint allows
     * users to remove notifications from their notification list. Users can only delete
     * their own notifications. Administrators with `manage_notifications` permission can
     * delete any notification. This action cannot be undone and will permanently remove
     * the UserNotification record from the database.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability. Users with `delete_notifications`
     * permission can delete their own notifications, while administrators with `manage_notifications`
     * permission can delete any notification.
     *
     * **Route Parameters:**
     * - `userNotification` (UserNotification, required): The user notification model instance to delete (route model binding)
     *
     * **Response:**
     * Returns a success message confirming the notification has been deleted. The response body
     * contains no data (null) as the notification no longer exists.
     *
     * **Note:** This operation cannot be reversed. The service layer ensures users can only
     * delete their own notifications unless they have administrative permissions.
     *
     * @response array{status: true, message: string, data: null}
     */
    public function __invoke(DeleteNotificationRequest $request, UserNotification $userNotification): JsonResponse
    {
        try {
            $user = $request->user();
            assert($user !== null);
            $this->userNotificationService->deleteNotification($user, $userNotification->id);

            return response()->apiSuccess(
                null,
                __('common.notification_deleted_successfully')
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
