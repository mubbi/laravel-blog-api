<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\FollowUserRequest;
use App\Models\User;
use App\Services\Interfaces\UserServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('User Social', weight: 1)]
final class FollowUserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService
    ) {}

    /**
     * Follow a User
     *
     * Allows authenticated users to follow another user. This creates a follow relationship
     * between the authenticated user and the target user. Users cannot follow themselves.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `follow_users` permission.
     *
     * **Route Parameters:**
     * - `user` (integer, required): User ID to follow (route model binding)
     *
     * **Response:**
     * Returns a success message indicating that the user has been followed. If the user
     * is already being followed, the operation is idempotent and returns success.
     *
     * @response array{status: true, message: string, data: null}
     */
    public function __invoke(FollowUserRequest $request, User $user): JsonResponse
    {
        try {
            /** @var User $currentUser */
            $currentUser = $request->user();

            $followed = $this->userService->followUser($user, $currentUser);

            if (! $followed) {
                return response()->apiSuccess(
                    null,
                    __('user.already_following')
                );
            }

            return response()->apiSuccess(
                null,
                __('user.followed_successfully')
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
