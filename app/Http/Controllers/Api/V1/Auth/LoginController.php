<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Resources\V1\Auth\UserResource;
use App\Services\Interfaces\AuthServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Authentication', weight: 0)]
final class LoginController extends BaseController
{
    public function __construct(private readonly AuthServiceInterface $authService) {}

    /**
     * Authenticate User and Generate Access Token
     *
     * Authenticates a user with email and password credentials. Upon successful authentication,
     * generates and returns a Laravel Sanctum bearer token along with the authenticated user's
     * profile information. The token should be included in the Authorization header for subsequent
     * authenticated requests using the format: `Bearer {token}`.
     *
     * **Request Body:**
     * - `email` (required, string): User's email address
     * - `password` (required, string): User's password
     *
     * **Response:**
     * Returns the authenticated user object with an access token embedded in the response.
     * The token is automatically included in the UserResource response and should be stored
     * securely by the client for future API requests.
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: UserResource}
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->login(
                $request->string('email')->toString(),
                $request->string('password')->toString()
            );

            /**
             * Successful login
             */
            return response()->apiSuccess(
                new UserResource($user),
                __('auth.login_success')
            );
        } catch (UnauthorizedException $e) {
            /**
             * Invalid login credentials
             *
             * @status 401
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return $this->handleException($e, $request, __('auth.failed'));
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
