<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Newsletter;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Newsletter\DeleteSubscriberRequest;
use App\Services\NewsletterService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Admin - Newsletter', weight: 3)]
final class DeleteSubscriberController extends Controller
{
    public function __construct(
        private readonly NewsletterService $newsletterService
    ) {}

    /**
     * Delete a newsletter subscriber
     *
     * Remove a subscriber from the newsletter list
     *
     * @response array{status: true, message: string, data: null}
     */
    public function __invoke(DeleteSubscriberRequest $request, int $id): JsonResponse
    {
        try {
            $this->newsletterService->deleteSubscriber($id, $request->validated());

            return response()->apiSuccess(
                null,
                __('common.subscriber_deleted')
            );
        } catch (ModelNotFoundException $e) {
            /**
             * Subscriber not found
             *
             * @status 404
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return response()->apiError(
                __('common.subscriber_not_found'),
                Response::HTTP_NOT_FOUND
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
