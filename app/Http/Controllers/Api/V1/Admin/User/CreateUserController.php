<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\CreateUserRequest;
use App\Http\Resources\V1\Admin\User\UserDetailResource;
use App\Services\UserService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Admin - User Management', weight: 2)]
final class CreateUserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Create User
     *
     * Create a new user with specified details and optional role assignment
     *
     * @response array{status: true, message: string, data: UserDetailResource}
     */
    public function __invoke(CreateUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validated());

            return response()->apiSuccess(
                new UserDetailResource($user),
                __('common.user_created_successfully'),
                Response::HTTP_CREATED
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
