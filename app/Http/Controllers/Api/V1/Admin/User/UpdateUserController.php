<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\User;

use App\Data\UpdateUserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\UpdateUserRequest;
use App\Http\Resources\V1\Admin\User\UserDetailResource;
use App\Models\User;
use App\Services\UserService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - User Management', weight: 2)]
final class UpdateUserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Update Existing User (Admin)
     *
     * Updates an existing user's information including profile details, password, and role
     * assignments. This admin endpoint supports partial updates - only fields provided in
     * the request body will be updated. All other fields remain unchanged. Role assignments
     * can be modified by providing a new array of role IDs.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `edit_users` permission.
     *
     * **Route Parameters:**
     * - `user` (User, required): The user model instance to update
     *
     * **Request Body (all fields optional):**
     * - `name` (string, max:255): User's full name
     * - `email` (email, max:255): User's email address (must be unique, excluding current user)
     * - `password` (string, min:8, max:255): New password for the user
     * - `avatar_url` (url|null, max:255): URL to user's avatar image (null to clear)
     * - `bio` (string|null, max:1000): User's biography or description (null to clear)
     * - `twitter` (string|null, max:255): Twitter/X profile handle (null to clear)
     * - `facebook` (string|null, max:255): Facebook profile URL (null to clear)
     * - `linkedin` (string|null, max:255): LinkedIn profile URL (null to clear)
     * - `github` (string|null, max:255): GitHub profile username (null to clear)
     * - `website` (url|null, max:255): Personal website URL (null to clear)
     * - `role_ids` (array of integers): Array of role IDs to assign to the user (replaces existing roles)
     *
     * **Response:**
     * Returns the updated user object with all changes reflected, including updated roles
     * and permissions.
     *
     * @response array{status: true, message: string, data: UserDetailResource}
     */
    public function __invoke(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            $dto = UpdateUserDTO::fromRequest($request);
            $user = $this->userService->updateUser($user, $dto);

            return response()->apiSuccess(
                new UserDetailResource($user),
                __('common.user_updated_successfully')
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
