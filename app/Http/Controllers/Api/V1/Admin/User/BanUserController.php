<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\BanUserRequest;
use App\Http\Resources\V1\Admin\User\UserDetailResource;
use App\Services\UserService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Admin - User Management', weight: 2)]
final class BanUserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Ban User
     *
     * Ban a user from accessing the system
     *
     * @response array{status: true, message: string, data: UserDetailResource}
     */
    public function __invoke(int $id, BanUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->banUser($id);

            return response()->apiSuccess(
                new UserDetailResource($user),
                __('common.user_banned_successfully')
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
