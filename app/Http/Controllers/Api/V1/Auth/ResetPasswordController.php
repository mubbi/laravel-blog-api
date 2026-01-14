<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Requests\V1\Auth\ResetPasswordRequest;
use App\Services\Interfaces\AuthServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Throwable;

#[Group('Authentication', weight: 0)]
final class ResetPasswordController extends BaseController
{
    public function __construct(private readonly AuthServiceInterface $authService) {}

    /**
     * Reset User Password
     *
     * Resets the user's password using a valid reset token received via email.
     * After a successful password reset, all existing authentication tokens
     * for the user will be revoked for security purposes.
     *
     * **Request Body:**
     * - `email` (required, string): User's email address
     * - `token` (required, string): Password reset token from email
     * - `password` (required, string): New password (must meet password requirements)
     * - `password_confirmation` (required, string): Password confirmation
     *
     * **Response:**
     * Returns a success message indicating that the password has been reset successfully.
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: null}
     */
    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->resetPassword(
                $request->string('email')->toString(),
                $request->string('token')->toString(),
                $request->string('password')->toString()
            );

            /**
             * Password reset successfully
             */
            return response()->apiSuccess(
                null,
                __('passwords.reset')
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
