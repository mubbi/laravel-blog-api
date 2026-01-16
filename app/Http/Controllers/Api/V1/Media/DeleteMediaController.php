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

#[Group('Media Management', weight: 3)]
final class DeleteMediaController extends Controller
{
    public function __construct(
        private readonly MediaServiceInterface $mediaService
    ) {}

    /**
     * Delete Media File
     *
     * Deletes a media file from both the storage disk and the database. This action is
     * irreversible. Only the user who uploaded the media or an administrator can delete it.
     *
     * **Access Control:**
     * - **Media owner**: Can delete their own media
     * - **Administrators**: Can delete any media
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and appropriate permissions.
     *
     * **Response:**
     * Returns a success message confirming the media has been deleted.
     * The response includes HTTP 200 OK status code.
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
