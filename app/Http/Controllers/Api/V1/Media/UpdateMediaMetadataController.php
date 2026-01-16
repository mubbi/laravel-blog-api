<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Media;

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

#[Group('Media Management', weight: 3)]
final class UpdateMediaMetadataController extends Controller
{
    public function __construct(
        private readonly MediaServiceInterface $mediaService
    ) {}

    /**
     * Update Media Metadata
     *
     * Updates metadata for a media file such as name, alt text, caption, and description.
     * This does not modify the actual file, only its metadata in the database.
     *
     * **Access Control:**
     * - **Media owner**: Can update their own media metadata
     * - **Administrators**: Can update any media metadata
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and appropriate permissions.
     *
     * **Request Body:**
     * - `name` (optional, string, max:255): Custom name for the media
     * - `alt_text` (optional, string, max:500): Alt text for accessibility
     * - `caption` (optional, string, max:500): Caption for the media
     * - `description` (optional, string, max:1000): Description of the media
     *
     * **Response:**
     * Returns the updated media object with all details.
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
