<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\DeleteUserRequest;
use App\Models\User;
use App\Services\Interfaces\UserServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - User Management', weight: 2)]
final class DeleteUserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService
    ) {}

    /**
     * Permanently Delete User (Admin)
     *
     * Permanently deletes a user account from the system. This action cannot be undone and
     * will remove all user data including profile information, associated records, and
     * authentication tokens. Users cannot delete their own account through this endpoint.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `delete_users` permission.
     *
     * **Route Parameters:**
     * - `user` (User, required): The user model instance to delete
     *
     * **Response:**
     * Returns a success message confirming the user has been deleted. The response body
     * contains no data (null) as the user no longer exists.
     *
     * **Note:** This operation cannot be reversed. Consider using user blocking/banning
     * instead if temporary account suspension is desired.
     *
     * @response array{status: true, message: string, data: null}
     */
    public function __invoke(DeleteUserRequest $request, User $user): JsonResponse
    {
        try {
            $currentUser = $request->user();
            assert($currentUser !== null);
            $this->userService->deleteUser($user, $currentUser);

            return response()->apiSuccess(
                null,
                __('common.user_deleted_successfully')
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
