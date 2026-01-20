<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\BanUserRequest;
use App\Http\Resources\V1\User\UserDetailResource;
use App\Models\User;
use App\Services\Interfaces\UserServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('User Management', weight: 2)]
final class BanUserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService
    ) {}

    /**
     * Ban User Account (Admin)
     *
     * Bans a user account, preventing them from accessing the system. Banned users cannot
     * authenticate or perform any actions. All existing sessions and tokens are invalidated.
     * Users cannot ban their own account through this endpoint.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `ban_users` permission.
     *
     * **Route Parameters:**
     * - `user` (User, required): The user model instance to ban
     *
     * **Response:**
     * Returns the updated user object with the banned status reflected. The user's account
     * status will be set to "banned" and all active sessions will be terminated.
     *
     * **Note:** Banned users can be unbanned using the Unban User endpoint. This is a
     * reversible action unlike deletion.
     *
     * @response array{status: true, message: string, data: UserDetailResource}
     */
    public function __invoke(User $user, BanUserRequest $request): JsonResponse
    {
        try {
            $currentUser = $request->user();
            assert($currentUser !== null);
            $user = $this->userService->banUser($user, $currentUser);

            return response()->apiSuccess(
                new UserDetailResource($user),
                __('common.user_banned_successfully')
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
