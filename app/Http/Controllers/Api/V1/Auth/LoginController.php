<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Resources\V1\Auth\UserResource;
use App\Services\Interfaces\AuthServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

#[Group('Authentication', weight: 0)]
final class LoginController extends Controller
{
    /** @phpstan-ignore-next-line property.onlyWritten */
    public function __construct(private readonly AuthServiceInterface $authService) {}

    /**
     * Login API
     *
     * Handle an authentication attempt and return a Sanctum token
     *
     * @unauthenticated
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->login(
                $request->string('email')->toString(),
                $request->string('password')->toString()
            );

            return response()->apiSuccess(
                new UserResource($user),
                __('auth.login_success')
            );
        } catch (UnauthorizedException $e) {
            return response()->apiError(__('auth.failed'), Response::HTTP_UNAUTHORIZED);
        } catch (\Throwable $e) {
            return response()->apiError(__('An unexpected error occurred.'), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
