<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Media;

use App\Data\UpdateMediaMetadataDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Media\UpdateMediaMetadataRequest;
use App\Http\Resources\V1\Media\MediaResource;
use App\Models\Media;
use App\Services\Interfaces\MediaServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - Media Management', weight: 4)]
final class UpdateMediaMetadataController extends Controller
{
    public function __construct(
        private readonly MediaServiceInterface $mediaService
    ) {}

    /**
     * Update Media Metadata (Admin)
     *
     * Updates metadata for any media file in the system such as name, alt text, caption, and description.
     * This admin endpoint allows administrators to update metadata for any media file, regardless of
     * who uploaded it. This does not modify the actual file, only its metadata in the database.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `manage_media` permission.
     * Administrators can update metadata for any media file.
     *
     * **Route Parameters:**
     * - `media` (Media, required): The media model instance to update (route model binding)
     *
     * **Request Body (all fields optional):**
     * - `name` (string, max:255): Custom name for the media
     * - `alt_text` (string, max:500): Alt text for accessibility
     * - `caption` (string, max:500): Caption for the media
     * - `description` (string, max:1000): Description of the media
     *
     * **Response:**
     * Returns the updated media object with all details including the modified metadata.
     *
     * **Note:** This admin endpoint allows updating any media file. For user-specific media
     * updates with ownership checks, use the user media endpoints.
     *
     * @response array{status: true, message: string, data: MediaResource}
     */
    public function __invoke(UpdateMediaMetadataRequest $request, Media $media): JsonResponse
    {
        try {
            $dto = UpdateMediaMetadataDTO::fromRequest($request);
            $updatedMedia = $this->mediaService->updateMediaMetadata($media->id, $dto);

            return response()->apiSuccess(
                new MediaResource($updatedMedia),
                __('common.media_updated_successfully')
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
