<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Media\DeleteMediaRequest;
use App\Models\Media;
use App\Services\Interfaces\MediaServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Media Management', weight: 4)]
final class DeleteMediaController extends Controller
{
    public function __construct(
        private readonly MediaServiceInterface $mediaService
    ) {}

    /**
     * Delete Media File (Admin)
     *
     * Deletes a media file from both the storage disk and the database. This admin endpoint
     * allows administrators to delete any media file in the system, regardless of who uploaded it.
     * This action is irreversible and will permanently remove the file and all associated data.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `manage_media` permission.
     * Administrators can delete any media file.
     *
     * **Route Parameters:**
     * - `media` (Media, required): The media model instance to delete (route model binding)
     *
     * **Response:**
     * Returns a success message confirming the media has been deleted. The response body
     * contains no data (null) as the media no longer exists.
     *
     * **Note:** This operation cannot be reversed. The file will be permanently deleted from
     * storage and the database. For user-specific media deletion with ownership checks,
     * use the user media endpoints.
     *
     * @response array{status: true, message: string, data: null}
     */
    public function __invoke(DeleteMediaRequest $request, Media $media): JsonResponse
    {
        try {
            $this->mediaService->deleteMedia($media->id);

            return response()->apiSuccess(
                null,
                __('common.media_deleted_successfully')
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
