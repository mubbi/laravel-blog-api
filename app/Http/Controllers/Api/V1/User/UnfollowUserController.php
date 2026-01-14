<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\UnfollowUserRequest;
use App\Models\User;
use App\Services\Interfaces\UserServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('User Social', weight: 1)]
final class UnfollowUserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService
    ) {}

    /**
     * Unfollow a User
     *
     * Allows authenticated users to unfollow another user. This removes the follow relationship
     * between the authenticated user and the target user. Users cannot unfollow themselves.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `unfollow_users` permission.
     *
     * **Route Parameters:**
     * - `user` (integer, required): User ID to unfollow (route model binding)
     *
     * **Response:**
     * Returns a success message indicating that the user has been unfollowed. If the user
     * is not being followed, the operation is idempotent and returns success.
     *
     * @response array{status: true, message: string, data: null}
     */
    public function __invoke(UnfollowUserRequest $request, User $user): JsonResponse
    {
        try {
            /** @var User $currentUser */
            $currentUser = $request->user();

            $unfollowed = $this->userService->unfollowUser($user, $currentUser);

            if (! $unfollowed) {
                return response()->apiSuccess(
                    null,
                    __('user.not_following')
                );
            }

            return response()->apiSuccess(
                null,
                __('user.unfollowed_successfully')
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
