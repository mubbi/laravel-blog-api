<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Data\User\FilterUserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\GetUsersRequest;
use App\Http\Resources\MetaResource;
use App\Http\Resources\V1\User\UserDetailResource;
use App\Services\Interfaces\UserServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('User Management', weight: 2)]
final class GetUsersController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService
    ) {}

    /**
     * Get Paginated List of Users (Admin)
     *
     * Retrieves a paginated list of all users in the system with comprehensive filtering, sorting,
     * and search capabilities. This admin endpoint provides full user management capabilities including
     * filtering by role, account status (active, banned, blocked), and search functionality.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `view_users` permission.
     *
     * **Query Parameters (all optional):**
     * - `page` (integer, min:1, default: 1): Page number for pagination
     * - `per_page` (integer, min:1, max:100, default: 15): Number of users per page
     * - `search` (string, max:255): Search term to filter users by name or email
     * - `role_id` (integer): Filter users by specific role ID
     * - `status` (enum: active|banned|blocked): Filter users by account status
     * - `created_after` (date, Y-m-d format): Filter users created on or after this date
     * - `created_before` (date, Y-m-d format): Filter users created on or before this date
     * - `sort_by` (enum: name|email|created_at|updated_at, default: created_at): Field to sort by
     * - `sort_direction` (enum: asc|desc, default: desc): Sort direction
     *
     * **Response:**
     * Returns a paginated collection of users with detailed information including roles, permissions,
     * account status, and metadata. Includes pagination metadata with total count, current page,
     * per page limit, and pagination links.
     *
     * @response array{status: true, message: string, data: array{users: UserDetailResource[], meta: MetaResource}}
     */
    public function __invoke(GetUsersRequest $request): JsonResponse
    {
        try {
            $dto = FilterUserDTO::fromRequest($request);
            $users = $this->userService->getUsers($dto);

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
