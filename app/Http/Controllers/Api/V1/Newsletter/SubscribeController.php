<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Newsletter;

use App\Data\Newsletter\SubscribeNewsletterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Newsletter\SubscribeRequest;
use App\Services\Interfaces\NewsletterServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Newsletter', weight: 4)]
final class SubscribeController extends Controller
{
    public function __construct(
        private readonly NewsletterServiceInterface $newsletterService
    ) {}

    /**
     * Subscribe to Newsletter
     *
     * Allows users to subscribe to the newsletter by providing their email address.
     * If the user is authenticated, their user ID will be automatically linked to the subscription.
     * A verification email will be sent to the provided email address to confirm the subscription.
     *
     * **Request Body:**
     * - `email` (required, string, email): Email address to subscribe
     *
     * **Response:**
     * Returns a success message indicating that a verification token has been sent to the provided
     * email address. The verification email containing the token will be sent to the provided email
     * address. The subscriber will be in an unverified state until they verify their email address
     * using the verification endpoint.
     *
     * **Note:** If the email is already subscribed and verified, a new verification token will
     * still be generated and sent. If the email is subscribed but not verified, a new verification
     * token will be generated and sent.
     *
     * **Security:** The verification token is never returned in the API response. It is only sent
     * via email to ensure that only the email owner can verify the subscription.
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: null}
     */
    public function __invoke(SubscribeRequest $request): JsonResponse
    {
        try {
            $dto = SubscribeNewsletterDTO::fromRequest($request);
            $this->newsletterService->subscribe($dto);

            return response()->apiSuccess(
                null,
                __('newsletter.verification_token_sent')
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
