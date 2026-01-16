<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\ShowUserRequest;
use App\Http\Resources\V1\User\UserDetailResource;
use App\Models\User;
use App\Services\Interfaces\UserServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('User Management', weight: 2)]
final class ShowUserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService
    ) {}

    /**
     * Get Single User by ID (Admin)
     *
     * Retrieves detailed information about a specific user by their ID. This admin endpoint
     * provides complete user data including profile information, roles, permissions, account
     * status, and all associated metadata. Used for viewing user details in admin panels
     * and user management interfaces.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `view_users` permission.
     *
     * **Route Parameters:**
     * - `user` (User, required): The user model instance to retrieve
     *
     * **Response:**
     * Returns the complete user object with all associated data including roles, permissions,
     * account status, profile information, and metadata.
     *
     * @response array{status: true, message: string, data: UserDetailResource}
     */
    public function __invoke(User $user, ShowUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->getUserWithRelationships($user);

            return response()->apiSuccess(
                new UserDetailResource($user),
                __('common.success')
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
