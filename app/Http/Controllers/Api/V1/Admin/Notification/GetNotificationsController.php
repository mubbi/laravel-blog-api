<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Notification\GetNotificationsRequest;
use App\Http\Resources\MetaResource;
use App\Http\Resources\V1\Notification\NotificationResource;
use App\Services\NotificationService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

#[Group('Admin - Notifications', weight: 4)]
final class GetNotificationsController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Get all notifications for admin management
     *
     * Retrieve a paginated list of all notifications with filtering and sorting options
     *
     * @response array{status: true, message: string, data: array{notifications: NotificationResource[], meta: MetaResource}}
     */
    public function __invoke(GetNotificationsRequest $request): JsonResponse
    {
        try {
            $notifications = $this->notificationService->getNotifications($request->validated());

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
        } catch (\Throwable $e) {
            Log::error('Notifications retrieval failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

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
