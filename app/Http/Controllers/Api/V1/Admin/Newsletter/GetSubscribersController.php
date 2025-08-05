<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Newsletter;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Newsletter\GetSubscribersRequest;
use App\Http\Resources\MetaResource;
use App\Http\Resources\V1\Newsletter\NewsletterSubscriberResource;
use App\Services\NewsletterService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

#[Group('Admin - Newsletter', weight: 3)]
final class GetSubscribersController extends Controller
{
    public function __construct(
        private readonly NewsletterService $newsletterService
    ) {}

    /**
     * Get all newsletter subscribers for admin management
     *
     * Retrieve a paginated list of all newsletter subscribers with filtering and sorting options
     *
     * @response array{status: true, message: string, data: array{subscribers: NewsletterSubscriberResource[], meta: MetaResource}}
     */
    public function __invoke(GetSubscribersRequest $request): JsonResponse
    {
        try {
            $subscribers = $this->newsletterService->getSubscribers($request->validated());

            $subscriberCollection = NewsletterSubscriberResource::collection($subscribers);
            $subscriberCollectionData = $subscriberCollection->response()->getData(true);

            // Ensure we have the expected array structure
            if (! is_array($subscriberCollectionData) || ! isset($subscriberCollectionData['data'], $subscriberCollectionData['meta'])) {
                throw new \RuntimeException(__('common.unexpected_response_format'));
            }

            return response()->apiSuccess(
                [
                    'subscribers' => $subscriberCollectionData['data'],
                    'meta' => MetaResource::make($subscriberCollectionData['meta']),
                ],
                __('common.success')
            );
        } catch (\Throwable $e) {
            Log::error('Newsletter subscribers retrieval failed', [
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
