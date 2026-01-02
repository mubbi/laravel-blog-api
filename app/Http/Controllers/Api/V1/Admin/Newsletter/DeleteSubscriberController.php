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
use Throwable;

#[Group('Admin - Newsletter', weight: 3)]
final class DeleteSubscriberController extends Controller
{
    public function __construct(
        private readonly NewsletterService $newsletterService
    ) {}

    /**
     * Delete Newsletter Subscriber (Admin)
     *
     * Removes a subscriber from the newsletter subscription list. This permanently deletes
     * the subscriber record and they will no longer receive newsletter emails. Typically
     * used when a subscriber requests removal or when cleaning up the subscriber database.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `manage_newsletter_subscribers` permission.
     *
     * **Route Parameters:**
     * - `id` (integer, required): The unique identifier of the subscriber to remove
     *
     * **Response:**
     * Returns a success message confirming the subscriber has been removed. The response
     * body contains no data (null) as the subscriber record no longer exists.
     *
     * **Note:** This operation cannot be reversed. The subscriber will need to resubscribe
     * if they wish to receive newsletters again in the future.
     *
     * @response array{status: true, message: string, data: null}
     */
    public function __invoke(DeleteSubscriberRequest $request, int $id): JsonResponse
    {
        try {
            $this->newsletterService->deleteSubscriber($id);

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
            return $this->handleException($e, $request);
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
