<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Data\FilterUserFollowersDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\GetUserFollowersRequest;
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
final class GetUserFollowersController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService
    ) {}

    /**
     * Get User Followers
     *
     * Retrieves a paginated list of users who are following the specified user. This endpoint
     * supports pagination and sorting options. The response includes user details, roles,
     * and follower counts.
     *
     * **Authentication:**
     * This is a public endpoint, but authentication may be required for private profiles.
     *
     * **Route Parameters:**
     * - `user` (integer, required): User ID to get followers for (route model binding)
     *
     * **Query Parameters (all optional):**
     * - `page` (integer, min:1, default: 1): Page number for pagination
     * - `per_page` (integer, min:1, max:100, default: 15): Number of followers per page
     * - `sort_by` (enum: name|created_at|updated_at, default: created_at): Field to sort by
     * - `sort_direction` (enum: asc|desc, default: desc): Sort direction
     *
     * **Response:**
     * Returns a paginated collection of users who are following the specified user, with
     * metadata including total count, current page, per page limit, and pagination links.
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: array{followers: UserResource[], meta: MetaResource}}
     */
    public function __invoke(GetUserFollowersRequest $request, User $user): JsonResponse
    {
        try {
            $dto = FilterUserFollowersDTO::fromRequest($request);
            $followers = $this->userService->getFollowers($user, $dto);

            $followerCollection = UserResource::collection($followers);

            /**
             * Successful followers retrieval
             */
            $followerCollectionData = $followerCollection->response()->getData(true);

            // Ensure we have the expected array structure
            if (! is_array($followerCollectionData) || ! isset($followerCollectionData['data'], $followerCollectionData['meta'])) {
                throw new RuntimeException(__('common.unexpected_response_format'));
            }

            return response()->apiSuccess(
                [
                    'followers' => $followerCollectionData['data'],
                    'meta' => new MetaResource($followerCollectionData['meta']),
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
