<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\User;

use App\Data\CreateUserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\CreateUserRequest;
use App\Http\Resources\V1\Admin\User\UserDetailResource;
use App\Services\UserService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - User Management', weight: 2)]
final class CreateUserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Create New User (Admin)
     *
     * Creates a new user account with the specified details. This admin endpoint allows creating
     * users with complete profile information and optional role assignment. The newly created
     * user will be able to authenticate using the provided email and password.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `create_users` permission.
     *
     * **Request Body:**
     * - `name` (required, string, max:255): User's full name
     * - `email` (required, email, max:255, unique): User's email address (must be unique)
     * - `password` (required, string, min:8, max:255): User's password (minimum 8 characters)
     * - `avatar_url` (optional, url, max:255): URL to user's avatar image
     * - `bio` (optional, string, max:1000): User's biography or description
     * - `twitter` (optional, string, max:255): Twitter/X profile handle or URL
     * - `facebook` (optional, string, max:255): Facebook profile URL
     * - `linkedin` (optional, string, max:255): LinkedIn profile URL
     * - `github` (optional, string, max:255): GitHub profile username or URL
     * - `website` (optional, url, max:255): Personal website URL
     * - `role_id` (optional, integer): ID of the role to assign to the user
     *
     * **Response:**
     * Returns the newly created user object with all details including assigned roles and
     * permissions. The response includes HTTP 201 Created status code.
     *
     * @response array{status: true, message: string, data: UserDetailResource}
     */
    public function __invoke(CreateUserRequest $request): JsonResponse
    {
        try {
            $dto = CreateUserDTO::fromRequest($request);
            $user = $this->userService->createUser($dto);

            return response()->apiSuccess(
                new UserDetailResource($user),
                __('common.user_created_successfully'),
                Response::HTTP_CREATED
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
