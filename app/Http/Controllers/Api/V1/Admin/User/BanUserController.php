<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\BanUserRequest;
use App\Http\Resources\V1\Admin\User\UserDetailResource;
use App\Services\UserService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - User Management', weight: 2)]
final class BanUserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
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
     * - `id` (integer, required): The unique identifier of the user to ban
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
    public function __invoke(int $id, BanUserRequest $request): JsonResponse
    {
        try {
            $currentUser = $request->user();
            assert($currentUser !== null);
            $user = $this->userService->banUser($id, $currentUser->id);

            return response()->apiSuccess(
                new UserDetailResource($user),
                __('common.user_banned_successfully')
            );
        } catch (AuthorizationException $e) {
            /**
             * Forbidden - Cannot ban self
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
