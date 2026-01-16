<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\UnblockUserRequest;
use App\Http\Resources\V1\User\UserDetailResource;
use App\Models\User;
use App\Services\Interfaces\UserServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('User Management', weight: 2)]
final class UnblockUserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService
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
     * - `user` (User, required): The user model instance to unblock
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
    public function __invoke(User $user, UnblockUserRequest $request): JsonResponse
    {
        try {
            $currentUser = $request->user();
            assert($currentUser !== null);
            $user = $this->userService->unblockUser($user, $currentUser);

            return response()->apiSuccess(
                new UserDetailResource($user),
                __('common.user_unblocked_successfully')
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
