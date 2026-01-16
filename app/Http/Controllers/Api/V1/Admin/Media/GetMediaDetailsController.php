<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Media\GetMediaDetailsRequest;
use App\Http\Resources\V1\Media\MediaResource;
use App\Models\Media;
use App\Services\Interfaces\MediaServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - Media Management', weight: 4)]
final class GetMediaDetailsController extends Controller
{
    public function __construct(
        private readonly MediaServiceInterface $mediaService
    ) {}

    /**
     * Get Media Details (Admin)
     *
     * Retrieves detailed information about a specific media file including metadata,
     * file information, and uploader details. This admin endpoint provides full access
     * to any media file in the system, regardless of who uploaded it.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `view_media` permission.
     * Administrators with `manage_media` permission can view any media details.
     *
     * **Route Parameters:**
     * - `media` (Media, required): The media model instance to retrieve (route model binding)
     *
     * **Response:**
     * Returns the media object with all details including URL, metadata, uploader information,
     * and relationships.
     *
     * **Note:** This admin endpoint allows viewing any media file. For user-specific media
     * access with ownership checks, use the user media endpoints.
     *
     * @response array{status: true, message: string, data: MediaResource}
     */
    public function __invoke(GetMediaDetailsRequest $request, Media $media): JsonResponse
    {
        try {
            $media = $this->mediaService->getMediaById($media->id);

            return response()->apiSuccess(
                new MediaResource($media),
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
