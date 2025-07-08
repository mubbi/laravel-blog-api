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
    /** @phpstan-ignore-next-line property.onlyWritten */
    public function __construct(private readonly AuthServiceInterface $authService) {}

    /**
     * Refresh Token API
     *
     * Refresh the access token using a valid refresh token.
     */
    public function __invoke(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->refreshToken(
                $request->string('refresh_token')->toString()
            );

            return response()->apiSuccess(
                new UserResource($user),
                __('auth.token_refreshed_successfully')
            );
        } catch (UnauthorizedException $e) {
            return response()->apiError($e->getMessage(), Response::HTTP_UNAUTHORIZED);
        } catch (\Throwable $e) {
            return response()->apiError(__('An unexpected error occurred.'), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
