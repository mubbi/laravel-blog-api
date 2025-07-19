<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Auth\UserResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('User', weight: 0)]
class MeController extends Controller
{
    /**
     * User Profile API
     *
     * Handle the incoming request to get the authenticated user.
     *
     * @response array{status: true, message: string, data: UserResource}
     */
    public function __invoke(Request $request): JsonResponse
    {
        /**
         * Successful response
         */

        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->load(['roles.permissions']);

        return response()->apiSuccess(
            new \App\Http\Resources\V1\Auth\UserResource($user),
            __('common.success')
        );
    }
}
