<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\GetUsersRequest;
use App\Http\Resources\MetaResource;
use App\Http\Resources\V1\Admin\User\UserDetailResource;
use App\Services\UserService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Admin - User Management', weight: 2)]
final class GetUsersController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Get Users List
     *
     * Retrieve a paginated list of users with optional filtering by role, status, and search terms
     *
     * @response array{status: true, message: string, data: array{users: UserDetailResource[], meta: MetaResource}}
     */
    public function __invoke(GetUsersRequest $request): JsonResponse
    {
        try {
            $params = $request->withDefaults();

            $users = $this->userService->getUsers($params);

            $userCollection = UserDetailResource::collection($users);

            /**
             * Successful users retrieval
             */
            $userCollectionData = $userCollection->response()->getData(true);

            // Ensure we have the expected array structure
            if (! is_array($userCollectionData) || ! isset($userCollectionData['data'], $userCollectionData['meta'])) {
                throw new \RuntimeException(__('common.unexpected_response_format'));
            }

            return response()->apiSuccess(
                [
                    'users' => $userCollectionData['data'],
                    'meta' => MetaResource::make($userCollectionData['meta']),
                ],
                __('common.success')
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
