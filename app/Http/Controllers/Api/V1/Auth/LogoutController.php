<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

final class LogoutController extends Controller
{
    /** @phpstan-ignore-next-line property.onlyWritten */
    public function __construct(private readonly AuthService $authService) {}

    /**
     * Logout user by revoking all tokens.
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $this->authService->logout($user);

            return response()->apiSuccess(
                null,
                __('auth.logout_success')
            );
        } catch (\Throwable $e) {
            return response()->apiError(__('An unexpected error occurred.'), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
