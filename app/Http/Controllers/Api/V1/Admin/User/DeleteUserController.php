<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\DeleteUserRequest;
use App\Services\UserService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Admin - User Management', weight: 2)]
final class DeleteUserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
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
     * - `id` (integer, required): The unique identifier of the user to delete
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
    public function __invoke(DeleteUserRequest $request, int $id): JsonResponse
    {
        try {
            $this->userService->deleteUser($id);

            return response()->apiSuccess(
                null,
                __('common.user_deleted_successfully')
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            /**
             * Forbidden - Cannot delete self
             *
             * @status 403
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return $this->handleException($e, $request);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            /**
             * User not found
             *
             * @status 404
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
