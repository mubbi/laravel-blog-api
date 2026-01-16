<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Media;

use App\Data\Media\FilterMediaDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Media\GetMediaLibraryRequest;
use App\Http\Resources\MetaResource;
use App\Http\Resources\V1\Media\MediaResource;
use App\Services\Interfaces\MediaServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Media Management', weight: 4)]
final class GetMediaLibraryController extends Controller
{
    public function __construct(
        private readonly MediaServiceInterface $mediaService
    ) {}

    /**
     * Get Media Library (Admin)
     *
     * Retrieves a paginated list of all media files in the system with comprehensive admin filtering,
     * sorting, and search capabilities. This admin endpoint provides full access to all media files
     * regardless of uploader, allowing administrators to manage the entire media library. Includes
     * filtering by type, search functionality, and advanced sorting options.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `view_media` permission.
     * Administrators with `manage_media` permission can view all media files.
     *
     * **Query Parameters (all optional):**
     * - `page` (integer, min:1, default: 1): Page number for pagination
     * - `per_page` (integer, min:1, max:100, default: 15): Number of media items per page
     * - `type` (enum: image|video|document|other): Filter by media type
     * - `search` (string, max:255): Search term to filter media by name, file name, or alt text
     * - `sort_by` (enum: name|file_name|type|size|created_at|updated_at, default: created_at): Field to sort by
     * - `sort_direction` (enum: asc|desc, default: desc): Sort direction
     *
     * **Response:**
     * Returns a paginated collection of all media items with metadata including total count,
     * current page, per page limit, and pagination links. Administrators can see all media
     * files regardless of uploader.
     *
     * **Note:** This admin endpoint returns all media files in the system. For user-specific
     * media access, use the user media endpoints.
     *
     * @response array{status: true, message: string, data: array{media: MediaResource[], meta: MetaResource}}
     */
    public function __invoke(GetMediaLibraryRequest $request): JsonResponse
    {
        try {
            $dto = FilterMediaDTO::fromRequest($request);

            // Apply user filtering based on permissions (non-managers see only their own media)
            $userIdForFiltering = $request->getUserIdForFiltering();
            if ($userIdForFiltering !== null) {
                $dto = $dto->withUploadedBy($userIdForFiltering);
            }

            $media = $this->mediaService->getMediaLibrary($dto);

            $mediaCollection = MediaResource::collection($media);

            /**
             * Successful media retrieval
             */
            $mediaCollectionData = $mediaCollection->response()->getData(true);

            // Ensure we have the expected array structure
            if (! is_array($mediaCollectionData) || ! isset($mediaCollectionData['data'], $mediaCollectionData['meta'])) {
                throw new RuntimeException(__('common.unexpected_response_format'));
            }

            return response()->apiSuccess(
                [
                    'media' => $mediaCollectionData['data'],
                    'meta' => MetaResource::make($mediaCollectionData['meta']),
                ],
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
