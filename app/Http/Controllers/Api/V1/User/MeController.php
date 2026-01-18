<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\GetMeRequest;
use App\Http\Resources\V1\Auth\UserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Throwable;

#[Group('User', weight: 0)]
final class MeController extends Controller
{
    /**
     * Get Authenticated User Profile
     *
     * Retrieves the complete profile information of the currently authenticated user, including
     * personal details, roles, and permissions. This endpoint is commonly used to populate user
     * profile pages, verify authentication status, and check user permissions and roles.
     *
     * **Authentication:**
     * Requires a valid Bearer token in the Authorization header. The user information is
     * automatically determined from the token.
     *
     * **Response:**
     * Returns the authenticated user's profile with all associated roles and permissions
     * loaded. The response includes user details, roles, and all available permissions.
     *
     * @response array{status: true, message: string, data: UserResource}
     */
    public function __invoke(GetMeRequest $request): JsonResponse
    {
        try {
            /**
             * Successful response
             */

            /** @var User $user */
            $user = $request->user();
            assert($user !== null);
            $user->load(['roles.permissions']);

            return response()->apiSuccess(
                new UserResource($user),
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
