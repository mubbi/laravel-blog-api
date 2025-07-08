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
    public function __construct(private readonly AuthServiceInterface $authService) {}

    /**
     * Login API
     *
     * Handle an authentication attempt and return a Sanctum token
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: UserResource}
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->login(
                $request->string('email')->toString(),
                $request->string('password')->toString()
            );

            /**
             * Successful login
             */
            return response()->apiSuccess(
                new UserResource($user),
                __('auth.login_success')
            );
        } catch (UnauthorizedException $e) {
            /**
             * Invalid login credentials
             *
             * @status 401
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return response()->apiError(__('auth.failed'), Response::HTTP_UNAUTHORIZED);
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
