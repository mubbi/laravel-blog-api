<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Newsletter;

use App\Data\Newsletter\FilterNewsletterSubscriberDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Newsletter\GetSubscribersRequest;
use App\Http\Resources\MetaResource;
use App\Http\Resources\V1\Newsletter\NewsletterSubscriberResource;
use App\Services\Interfaces\NewsletterServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Newsletter', weight: 3)]
final class GetSubscribersController extends Controller
{
    public function __construct(
        private readonly NewsletterServiceInterface $newsletterService
    ) {}

    /**
     * Get Paginated List of Newsletter Subscribers (Admin)
     *
     * Retrieves a paginated list of all newsletter subscribers with filtering and sorting
     * capabilities. This endpoint is used for managing the newsletter subscriber database,
     * viewing subscription statistics, and exporting subscriber lists for email campaigns.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `view_newsletter_subscribers` permission.
     *
     * **Query Parameters (all optional):**
     * - `page` (integer, min:1, default: 1): Page number for pagination
     * - `per_page` (integer, min:1, max:100, default: 15): Number of subscribers per page
     * - `search` (string, max:255): Search term to filter subscribers by email
     * - `status` (enum: verified|unverified): Filter subscribers by verification status
     * - `subscribed_at_from` (date, Y-m-d format): Filter subscribers who subscribed on or after this date
     * - `subscribed_at_to` (date, Y-m-d format): Filter subscribers who subscribed on or before this date (must be after or equal to subscribed_at_from)
     * - `sort_by` (enum: created_at|updated_at|email|is_verified, default: created_at): Field to sort by
     * - `sort_order` (enum: asc|desc, default: desc): Sort order
     *
     * **Response:**
     * Returns a paginated collection of newsletter subscribers with their email addresses,
     * verification status, subscription dates, and metadata. Includes pagination metadata
     * with total count, current page, per page limit, and pagination links.
     *
     * @response array{status: true, message: string, data: array{subscribers: NewsletterSubscriberResource[], meta: MetaResource}}
     */
    public function __invoke(GetSubscribersRequest $request): JsonResponse
    {
        try {
            $dto = FilterNewsletterSubscriberDTO::fromRequest($request);
            $subscribers = $this->newsletterService->getSubscribers($dto);

            $subscriberCollection = NewsletterSubscriberResource::collection($subscribers);
            $subscriberCollectionData = $subscriberCollection->response()->getData(true);

            // Ensure we have the expected array structure
            if (! is_array($subscriberCollectionData) || ! isset($subscriberCollectionData['data'], $subscriberCollectionData['meta'])) {
                throw new RuntimeException(__('common.unexpected_response_format'));
            }

            return response()->apiSuccess(
                [
                    'subscribers' => $subscriberCollectionData['data'],
                    'meta' => MetaResource::make($subscriberCollectionData['meta']),
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
