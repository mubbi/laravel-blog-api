<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Data\UpdateUserDTO;
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
     * Update Authenticated User Profile
     *
     * Updates the profile information of the currently authenticated user. Allows updating
     * personal details including name, avatar URL, bio, and social media links. All fields
     * are optional, allowing partial updates. Fields can be set to null to clear their values.
     *
     * **Request Body (all fields optional):**
     * - `name` (string|null): User's full name
     * - `avatar_url` (string|null): URL to user's avatar image
     * - `bio` (string|null): User's biography or description
     * - `twitter` (string|null): Twitter/X profile handle or URL
     * - `facebook` (string|null): Facebook profile URL
     * - `linkedin` (string|null): LinkedIn profile URL
     * - `github` (string|null): GitHub profile username or URL
     * - `website` (string|null): Personal website URL
     *
     * **Authentication:**
     * Requires a valid Bearer token in the Authorization header.
     *
     * **Note:** This endpoint only allows users to update their own profile. Users cannot
     * update their email, password, or roles through this endpoint (see admin endpoints for those).
     *
     * **Response:**
     * Returns the updated user profile with all changes reflected.
     *
     * @response array{status: true, message: string, data: UserResource}
     */
    public function __invoke(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $authenticatedUser = $request->user();
            assert($authenticatedUser !== null);

            $validated = $request->validated();

            // Track which optional fields were explicitly provided (for clearing support)
            $providedFields = [];
            $clearableFields = ['avatar_url', 'bio', 'twitter', 'facebook', 'linkedin', 'github', 'website'];
            foreach ($clearableFields as $field) {
                if (array_key_exists($field, $validated)) {
                    $providedFields[] = $field;
                }
            }

            $dto = new UpdateUserDTO(
                name: array_key_exists('name', $validated) ? ($validated['name'] !== null ? (string) $validated['name'] : null) : null,
                email: null,
                password: null,
                avatarUrl: array_key_exists('avatar_url', $validated) ? ($validated['avatar_url'] !== null ? (string) $validated['avatar_url'] : null) : null,
                bio: array_key_exists('bio', $validated) ? ($validated['bio'] !== null ? (string) $validated['bio'] : null) : null,
                twitter: array_key_exists('twitter', $validated) ? ($validated['twitter'] !== null ? (string) $validated['twitter'] : null) : null,
                facebook: array_key_exists('facebook', $validated) ? ($validated['facebook'] !== null ? (string) $validated['facebook'] : null) : null,
                linkedin: array_key_exists('linkedin', $validated) ? ($validated['linkedin'] !== null ? (string) $validated['linkedin'] : null) : null,
                github: array_key_exists('github', $validated) ? ($validated['github'] !== null ? (string) $validated['github'] : null) : null,
                website: array_key_exists('website', $validated) ? ($validated['website'] !== null ? (string) $validated['website'] : null) : null,
                roleIds: null,
                providedFields: $providedFields,
            );

            $user = $this->userService->updateUser($authenticatedUser->id, $dto);

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
            return $this->handleException($e, $request);
        }
    }
}
