<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\UpdateProfileRequest;
use App\Http\Resources\V1\User\UserResource;
use App\Services\UserService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('User Profile', weight: 1)]
final class UpdateProfileController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Update Profile
     *
     * Update the authenticated user's profile information
     *
     * @response array{status: true, message: string, data: UserResource}
     */
    public function __invoke(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $authenticatedUser = $request->user();
            assert($authenticatedUser !== null);

            $user = $this->userService->updateUser($authenticatedUser->id, $request->validated());

            return response()->apiSuccess(
                new UserResource($user),
                __('common.profile_updated_successfully')
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
