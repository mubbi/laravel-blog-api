<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Requests\V1\Auth\ForgotPasswordRequest;
use App\Services\Interfaces\AuthServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Throwable;

#[Group('Authentication', weight: 0)]
final class ForgotPasswordController extends BaseController
{
    public function __construct(private readonly AuthServiceInterface $authService) {}

    /**
     * Send Password Reset Link
     *
     * Sends a password reset link to the user's email address. The user will receive
     * an email containing a token that can be used to reset their password via the
     * password reset endpoint.
     *
     * **Request Body:**
     * - `email` (required, string): User's email address
     *
     * **Response:**
     * Returns a success message indicating that the password reset link has been sent.
     * For security reasons, the same response is returned whether the email exists or not.
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: null}
     */
    public function __invoke(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->forgotPassword(
                $request->string('email')->toString()
            );

            /**
             * Password reset link sent successfully
             */
            return response()->apiSuccess(
                null,
                __('passwords.sent')
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
