<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Newsletter;

use App\Data\Newsletter\VerifyUnsubscriptionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Newsletter\VerifyUnsubscriptionRequest;
use App\Http\Resources\V1\Newsletter\NewsletterSubscriberResource;
use App\Services\Interfaces\NewsletterServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Newsletter', weight: 4)]
final class VerifyUnsubscriptionController extends Controller
{
    public function __construct(
        private readonly NewsletterServiceInterface $newsletterService
    ) {}

    /**
     * Verify Newsletter Unsubscription
     *
     * Verifies a newsletter unsubscription using the verification token and email address sent
     * to the subscriber's email. Both the token and email must match to ensure that only the
     * email owner can verify the unsubscription. Once verified, the subscriber will be marked as
     * unsubscribed and will no longer receive newsletter emails.
     *
     * **Request Body:**
     * - `token` (required, string, size:64): Verification token received via email
     * - `email` (required, string, email): Email address that received the verification token
     *
     * **Response:**
     * Returns the unsubscribed subscriber object with unsubscribed_at set. The verification
     * token will be cleared from the database after successful verification.
     *
     * **Token Expiration:**
     * Verification tokens expire after a configured period (default: 24 hours). If the token
     * is invalid, expired, or doesn't match the email, a 404 error will be returned. Users will
     * need to request a new verification token by requesting unsubscription again.
     *
     * **Security:**
     * Both token and email are required to verify the unsubscription. This ensures that only the
     * person who received the email can verify the unsubscription, even if someone else obtains
     * the token.
     *
     * **Note:** If the token is invalid, expired, or doesn't match the email, a 404 error will
     * be returned. If the subscription is already unsubscribed, the existing unsubscribed record
     * will be returned.
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: NewsletterSubscriberResource}
     */
    public function __invoke(VerifyUnsubscriptionRequest $request): JsonResponse
    {
        try {
            $dto = VerifyUnsubscriptionDTO::fromRequest($request);
            $subscriber = $this->newsletterService->verifyUnsubscription($dto);

            return response()->apiSuccess(
                new NewsletterSubscriberResource($subscriber),
                __('common.subscriber_unsubscribed_successfully')
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
