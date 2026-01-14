<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Data\FilterUserFollowersDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\GetUserFollowingRequest;
use App\Http\Resources\MetaResource;
use App\Http\Resources\V1\User\UserResource;
use App\Models\User;
use App\Services\Interfaces\UserServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('User Social', weight: 1)]
final class GetUserFollowingController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService
    ) {}

    /**
     * Get Users That a User is Following
     *
     * Retrieves a paginated list of users that the specified user is following. This endpoint
     * supports pagination and sorting options. The response includes user details, roles,
     * and follower counts.
     *
     * **Authentication:**
     * This is a public endpoint, but authentication may be required for private profiles.
     *
     * **Route Parameters:**
     * - `user` (integer, required): User ID to get following list for (route model binding)
     *
     * **Query Parameters (all optional):**
     * - `page` (integer, min:1, default: 1): Page number for pagination
     * - `per_page` (integer, min:1, max:100, default: 15): Number of users per page
     * - `sort_by` (enum: name|created_at|updated_at, default: created_at): Field to sort by
     * - `sort_direction` (enum: asc|desc, default: desc): Sort direction
     *
     * **Response:**
     * Returns a paginated collection of users that the specified user is following, with
     * metadata including total count, current page, per page limit, and pagination links.
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: array{following: UserResource[], meta: MetaResource}}
     */
    public function __invoke(GetUserFollowingRequest $request, User $user): JsonResponse
    {
        try {
            $dto = FilterUserFollowersDTO::fromRequest($request);
            $following = $this->userService->getFollowing($user, $dto);

            $followingCollection = UserResource::collection($following);

            /**
             * Successful following retrieval
             */
            $followingCollectionData = $followingCollection->response()->getData(true);

            // Ensure we have the expected array structure
            if (! is_array($followingCollectionData) || ! isset($followingCollectionData['data'], $followingCollectionData['meta'])) {
                throw new RuntimeException(__('common.unexpected_response_format'));
            }

            return response()->apiSuccess(
                [
                    'following' => $followingCollectionData['data'],
                    'meta' => new MetaResource($followingCollectionData['meta']),
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
