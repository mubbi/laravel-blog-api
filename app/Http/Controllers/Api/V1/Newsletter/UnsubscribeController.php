<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Newsletter;

use App\Data\Newsletter\UnsubscribeNewsletterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Newsletter\UnsubscribeRequest;
use App\Services\Interfaces\NewsletterServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Newsletter', weight: 4)]
final class UnsubscribeController extends Controller
{
    public function __construct(
        private readonly NewsletterServiceInterface $newsletterService
    ) {}

    /**
     * Request Unsubscribe from Newsletter
     *
     * Allows users to request unsubscription from the newsletter by providing their email address.
     * A verification token will be sent to the provided email address to confirm the unsubscription.
     *
     * **Request Body:**
     * - `email` (required, string, email): Email address to unsubscribe
     *
     * **Response:**
     * Returns a success message indicating that an unsubscription token has been sent to the provided
     * email address. The verification email containing the token will be sent to the provided email
     * address. The subscriber will remain subscribed until they verify their unsubscription using the
     * verification endpoint.
     *
     * **Validation Requirements:**
     * - The email must exist in the subscriber list (404 if not found)
     * - The subscriber must be verified (422 if not verified)
     * - The subscriber must not already be unsubscribed (422 if already unsubscribed)
     *
     * **Security:** The verification token is never returned in the API response. It is only sent
     * via email to ensure that only the email owner can verify the unsubscription.
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: null}
     */
    public function __invoke(UnsubscribeRequest $request): JsonResponse
    {
        try {
            $dto = UnsubscribeNewsletterDTO::fromRequest($request);
            $this->newsletterService->unsubscribe($dto);

            return response()->apiSuccess(
                null,
                __('newsletter.unsubscription_token_sent')
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
