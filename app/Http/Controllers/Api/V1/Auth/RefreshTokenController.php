<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Requests\V1\Auth\RefreshTokenRequest;
use App\Http\Resources\V1\Auth\UserResource;
use App\Services\Interfaces\AuthServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

#[Group('Authentication', weight: 0)]
final class RefreshTokenController extends Controller
{
    public function __construct(private readonly AuthServiceInterface $authService) {}

    /**
     * Refresh Token API
     *
     * Refresh the access token using a valid refresh token.
     *
     * @response array{status: true, message: string, data: UserResource}
     */
    public function __invoke(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->refreshToken(
                $request->string('refresh_token')->toString()
            );

            /**
             * Successful Token Refresh
             */
            return response()->apiSuccess(
                new UserResource($user),
                __('auth.token_refreshed_successfully')
            );
        } catch (UnauthorizedException $e) {
            /**
             * Invalid login credentials
             *
             * @status 401
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return response()->apiError($e->getMessage(), Response::HTTP_UNAUTHORIZED);
        } catch (\Throwable $e) {
            /**
             * Internal server error
             *
             * @status 500
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return response()->apiError(__('common.something_went_wrong'), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
