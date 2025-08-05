<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\UnblockUserRequest;
use App\Http\Resources\V1\Admin\User\UserDetailResource;
use App\Services\UserService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Admin - User Management', weight: 2)]
final class UnblockUserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Unblock User
     *
     * Remove block from a user to restore their access to features
     *
     * @response array{status: true, message: string, data: UserDetailResource}
     */
    public function __invoke(int $id, UnblockUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->unblockUser($id);

            return response()->apiSuccess(
                new UserDetailResource($user),
                __('common.user_unblocked_successfully')
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            /**
             * Forbidden - Cannot unblock self
             *
             * @status 403
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return response()->apiError(
                $e->getMessage(),
                Response::HTTP_FORBIDDEN
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            /**
             * User not found
             *
             * @status 404
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return response()->apiError(
                __('common.user_not_found'),
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $e) {
            /**
             * Internal server error
             *
             * @status 500
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return response()->apiError(
                __('common.something_went_wrong'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
