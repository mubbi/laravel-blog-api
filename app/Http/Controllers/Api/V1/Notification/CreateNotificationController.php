<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Notification;

use App\Data\Notification\CreateNotificationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Notification\CreateNotificationRequest;
use App\Http\Resources\V1\Notification\NotificationResource;
use App\Services\Interfaces\NotificationServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Notifications', weight: 4)]
final class CreateNotificationController extends Controller
{
    public function __construct(
        private readonly NotificationServiceInterface $notificationService
    ) {}

    /**
     * Create and Send System Notification (Admin)
     *
     * Creates and sends a new system-wide notification to specified user audiences. Notifications
     * can be sent to all users, specific user groups (e.g., administrators), or individual users.
     * This endpoint is used for sending important announcements, system updates, or targeted
     * messages to user segments.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `send_notifications` permission.
     *
     * **Request Body:**
     * - `type` (required, enum): The type/classification of the notification
     * - `message` (required, array): Notification message content with the following structure:
     *   - `title` (required, string, max:255): Notification title/heading
     *   - `body` (required, string, max:255): Notification message body/content
     *   - `priority` (required, string, max:255): Notification priority level
     * - `audiences` (required, array, min:1): Array of target audience types. Each value must be one of:
     *   - `all_users`: Send to all registered users
     *   - `administrators`: Send to users with administrator role
     *   - `specific_users`: Send to specific user IDs (requires `user_ids` field)
     * - `user_ids` (required if audiences contains 'specific_users', array of integers): Array of user IDs to target when using 'specific_users' audience
     *
     * **Response:**
     * Returns the newly created notification object with all details including message content,
     * target audiences, delivery status, and metadata. The response includes HTTP 201 Created
     * status code.
     *
     * **Note:** Notifications are typically queued for delivery. The creation response indicates
     * the notification has been scheduled, but actual delivery may occur asynchronously.
     *
     * @response array{status: true, message: string, data: NotificationResource}
     */
    public function __invoke(CreateNotificationRequest $request): JsonResponse
    {
        try {
            $dto = CreateNotificationDTO::fromRequest($request);
            $notification = $this->notificationService->createNotification($dto);

            return response()->apiSuccess(
                new NotificationResource($notification),
                __('common.notification_created'),
                Response::HTTP_CREATED
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
