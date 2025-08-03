<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\DeleteUserRequest;
use App\Services\UserService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Admin - User Management', weight: 2)]
final class DeleteUserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Delete User
     *
     * Permanently delete a user from the system
     *
     * @response array{status: true, message: string, data: null}
     */
    public function __invoke(DeleteUserRequest $request, int $id): JsonResponse
    {
        try {
            $this->userService->deleteUser($id);

            return response()->apiSuccess(
                null,
                __('common.user_deleted_successfully')
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
