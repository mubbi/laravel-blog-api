<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\UnblockUserRequest;
use App\Http\Resources\V1\Admin\User\UserDetailResource;
use App\Services\UserService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - User Management', weight: 2)]
final class UnblockUserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Unblock User Account (Admin)
     *
     * Removes the block from a previously blocked user account, restoring their full access
     * to all features. The user's account status will be restored to active, and all feature
     * restrictions will be lifted. Users cannot unblock their own account through this endpoint.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `restore_users` permission.
     *
     * **Route Parameters:**
     * - `id` (integer, required): The unique identifier of the user to unblock
     *
     * **Response:**
     * Returns the updated user object with the block removed and full access restored.
     * The user's account status will be changed from "blocked" back to "active".
     *
     * **Note:** This endpoint only affects blocked users. Users with other statuses (e.g., banned)
     * should use the appropriate unban endpoint if needed.
     *
     * @response array{status: true, message: string, data: UserDetailResource}
     */
    public function __invoke(int $id, UnblockUserRequest $request): JsonResponse
    {
        try {
            $currentUser = $request->user();
            assert($currentUser !== null);
            $user = $this->userService->unblockUser($id, $currentUser->id);

            return response()->apiSuccess(
                new UserDetailResource($user),
                __('common.user_unblocked_successfully')
            );
        } catch (AuthorizationException $e) {
            /**
             * Forbidden - Cannot unblock self
             *
             * @status 403
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return $this->handleException($e, $request);
        } catch (ModelNotFoundException $e) {
            /**
             * User not found
             *
             * @status 404
             *
             * @body array{status: false, message: string, data: null, error: null}
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
