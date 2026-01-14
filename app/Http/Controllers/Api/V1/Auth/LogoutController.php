<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller as BaseController;
use App\Models\User;
use App\Services\Interfaces\AuthServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Authentication', weight: 0)]
final class LogoutController extends BaseController
{
    public function __construct(private readonly AuthServiceInterface $authService) {}

    /**
     * Logout Authenticated User
     *
     * Revokes all active Sanctum tokens for the authenticated user, effectively logging them out
     * from all devices and sessions. After a successful logout, all previously issued tokens
     * become invalid and cannot be used for authenticated requests.
     *
     * **Authentication:**
     * Requires a valid Bearer token in the Authorization header.
     *
     * **Response:**
     * Returns a success message indicating that all tokens have been revoked. The client should
     * discard the stored token and redirect the user to the login screen.
     *
     * @response array{status: true, message: string, data: null}
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $request->user();

            $this->authService->logout($user);

            /**
             * Successful Logout
             */
            return response()->apiSuccess(
                null,
                __('auth.logout_success')
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
