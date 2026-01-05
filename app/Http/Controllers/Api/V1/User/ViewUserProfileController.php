<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\ViewUserProfileRequest;
use App\Http\Resources\V1\User\UserResource;
use App\Models\User;
use App\Services\Interfaces\UserServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('User Social', weight: 1)]
final class ViewUserProfileController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService
    ) {}

    /**
     * View User Profile
     *
     * Retrieves the complete profile information of a specific user, including personal details,
     * roles, permissions, article count, comment count, followers count, and following count.
     * This endpoint is commonly used to display user profile pages.
     *
     * **Authentication:**
     * This is a public endpoint, but authentication may be required for private profiles.
     *
     * **Route Parameters:**
     * - `user` (integer, required): User ID to view profile for (route model binding)
     *
     * **Response:**
     * Returns the user's profile with all associated relationships loaded. The response
     * includes user details, roles, permissions, and social statistics.
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: UserResource}
     */
    public function __invoke(ViewUserProfileRequest $request, User $user): JsonResponse
    {
        try {
            $userProfile = $this->userService->getUserProfile($user);

            return response()->apiSuccess(
                new UserResource($userProfile),
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
