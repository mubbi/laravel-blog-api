<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\BlockUserRequest;
use App\Http\Resources\V1\User\UserDetailResource;
use App\Models\User;
use App\Services\Interfaces\UserServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('User Management', weight: 2)]
final class BlockUserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService
    ) {}

    /**
     * Block User Account (Admin)
     *
     * Blocks a user account, restricting their access to certain features while maintaining
     * their account data. Blocked users have limited functionality compared to banned users,
     * but the specific restrictions depend on the system implementation. Users cannot block
     * their own account through this endpoint.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `block_users` permission.
     *
     * **Route Parameters:**
     * - `user` (User, required): The user model instance to block
     *
     * **Response:**
     * Returns the updated user object with the blocked status reflected. The user's account
     * status will be set to "blocked" and feature access will be restricted accordingly.
     *
     * **Note:** Blocked users can be unblocked using the Unblock User endpoint. Blocking is
     * typically used for temporary restrictions, while banning is more permanent.
     *
     * @response array{status: true, message: string, data: UserDetailResource}
     */
    public function __invoke(User $user, BlockUserRequest $request): JsonResponse
    {
        try {
            $currentUser = $request->user();
            assert($currentUser !== null);
            $user = $this->userService->blockUser($user, $currentUser);

            return response()->apiSuccess(
                new UserDetailResource($user),
                __('common.user_blocked_successfully')
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
