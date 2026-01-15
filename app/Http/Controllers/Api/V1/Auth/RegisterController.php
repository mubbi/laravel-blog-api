<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Data\RegisterDTO;
use App\Http\Controllers\Controller as BaseController;
use App\Http\Requests\V1\Auth\RegisterRequest;
use App\Http\Resources\V1\Auth\UserResource;
use App\Services\Interfaces\AuthServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Authentication', weight: 0)]
final class RegisterController extends BaseController
{
    public function __construct(private readonly AuthServiceInterface $authService) {}

    /**
     * Register New User and Generate Access Token
     *
     * Registers a new user account with the provided information. Upon successful registration,
     * automatically generates and returns Laravel Sanctum bearer tokens (access and refresh tokens)
     * along with the newly created user's profile information. The user is automatically assigned
     * the "Subscriber" role by default. The tokens should be included in the Authorization header
     * for subsequent authenticated requests using the format: `Bearer {token}`.
     *
     * **Request Body:**
     * - `name` (required, string): User's full name
     * - `email` (required, string): User's email address (must be unique)
     * - `password` (required, string): User's password (must meet security requirements)
     * - `avatar_url` (optional, string): URL to user's avatar image
     * - `bio` (optional, string): User's biography (max 1000 characters)
     * - `twitter` (optional, string): Twitter username or handle
     * - `facebook` (optional, string): Facebook profile identifier
     * - `linkedin` (optional, string): LinkedIn profile identifier
     * - `github` (optional, string): GitHub username
     * - `website` (optional, string): Personal website URL
     *
     * **Response:**
     * Returns the newly created user object with access and refresh tokens embedded in the response.
     * The tokens are automatically included in the UserResource response and should be stored
     * securely by the client for future API requests.
     *
     * **Password Requirements:**
     * - Minimum 8 characters
     * - Must contain letters (mixed case)
     * - Must contain numbers
     * - Must contain symbols
     * - Must not appear in known data breaches
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: UserResource}
     */
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        try {
            $dto = RegisterDTO::fromRequest($request);
            $user = $this->authService->register($dto);

            /**
             * Successful registration
             */
            return response()->apiSuccess(
                new UserResource($user),
                __('auth.register_success'),
                Response::HTTP_CREATED
            );
        } catch (ValidationException $e) {
            /**
             * Validation error (e.g., email already exists)
             *
             * @status 422
             *
             * @body array{status: false, message: string, data: null, error: array<string, array<int, string>>}
             */
            return $this->handleException($e, $request);
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
