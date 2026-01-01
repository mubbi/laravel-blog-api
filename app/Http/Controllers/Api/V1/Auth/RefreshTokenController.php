<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Requests\V1\Auth\RefreshTokenRequest;
use App\Http\Resources\V1\Auth\UserResource;
use App\Services\Interfaces\AuthServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

#[Group('Authentication', weight: 0)]
final class RefreshTokenController extends BaseController
{
    public function __construct(private readonly AuthServiceInterface $authService) {}

    /**
     * Refresh Access Token
     *
     * Refreshes an expired or expiring access token using a valid refresh token. This endpoint
     * is used to obtain a new access token without requiring the user to re-authenticate with
     * their credentials. The refresh token is provided in the request body and must be valid
     * and not expired.
     *
     * **Request Body:**
     * - `refresh_token` (required, string): A valid refresh token previously issued by the authentication system
     *
     * **Response:**
     * Returns a new access token along with the user's profile information. The client should
     * update its stored token with the newly issued access token.
     *
     * **Note:** This endpoint does not require authentication with a bearer token, but requires
     * a valid refresh token in the request body.
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: UserResource}
     */
    public function __invoke(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->refreshToken(
                $request->string('refresh_token')->toString()
            );

            /**
             * Successful Token Refresh
             */
            return response()->apiSuccess(
                new UserResource($user),
                __('auth.token_refreshed_successfully')
            );
        } catch (UnauthorizedException $e) {
            /**
             * Invalid token
             *
             * @status 401
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return $this->handleException($e, $request);
        } catch (\Throwable $e) {
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
