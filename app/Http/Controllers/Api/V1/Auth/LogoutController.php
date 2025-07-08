<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Services\Interfaces\AuthServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

#[Group('Authentication', weight: 0)]
final class LogoutController extends Controller
{
    public function __construct(private readonly AuthServiceInterface $authService) {}

    /**
     * Logout API
     *
     * Logout user by revoking all tokens.
     *
     * @response array{status: true, message: string, data: null}
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $this->authService->logout($user);

            /**
             * Successful Logout
             */
            return response()->apiSuccess(
                null,
                __('auth.logout_success')
            );
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
