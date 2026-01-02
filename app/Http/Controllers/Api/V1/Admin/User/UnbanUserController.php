<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\UnbanUserRequest;
use App\Http\Resources\V1\Admin\User\UserDetailResource;
use App\Models\User;
use App\Services\UserService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - User Management', weight: 2)]
final class UnbanUserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Unban User Account (Admin)
     *
     * Removes the ban from a previously banned user account, restoring their access to the
     * system. The user will be able to authenticate again and perform actions according to
     * their role and permissions. Users cannot unban their own account through this endpoint.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `restore_users` permission.
     *
     * **Route Parameters:**
     * - `user` (User, required): The user model instance to unban
     *
     * **Response:**
     * Returns the updated user object with the ban removed and account status restored.
     * The user's account status will be changed from "banned" back to "active".
     *
     * **Note:** This endpoint only affects banned users. Users with other statuses (e.g., blocked)
     * should use the appropriate unblock endpoint if needed.
     *
     * @response array{status: true, message: string, data: UserDetailResource}
     */
    public function __invoke(UnbanUserRequest $request, User $user): JsonResponse
    {
        try {
            $currentUser = $request->user();
            assert($currentUser !== null);
            $user = $this->userService->unbanUser($user, $currentUser);

            return response()->apiSuccess(
                new UserDetailResource($user),
                __('common.user_unbanned_successfully')
            );
        } catch (AuthorizationException $e) {
            /**
             * Forbidden - Cannot unban self
             *
             * @status 403
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
