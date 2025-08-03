<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Notification\CreateNotificationRequest;
use App\Http\Resources\V1\Notification\NotificationResource;
use App\Services\NotificationService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Admin - Notifications', weight: 4)]
final class CreateNotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Create a new notification
     *
     * Create and send a new notification to specified audiences
     *
     * @response array{status: true, message: string, data: NotificationResource}
     */
    public function __invoke(CreateNotificationRequest $request): JsonResponse
    {
        try {
            $notification = $this->notificationService->createNotification($request->validated());

            return response()->apiSuccess(
                new NotificationResource($notification),
                __('common.notification_created'),
                Response::HTTP_CREATED
            );
        } catch (\Throwable $e) {
            /**
             * Internal server error
             *
             * @status 500
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return response()->apiError(
                __('common.something_went_wrong'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
