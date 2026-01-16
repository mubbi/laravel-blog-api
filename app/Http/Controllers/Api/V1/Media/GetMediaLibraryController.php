<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Media;

use App\Data\FilterMediaDTO;
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

#[Group('Media Management', weight: 3)]
final class GetMediaLibraryController extends Controller
{
    public function __construct(
        private readonly MediaServiceInterface $mediaService
    ) {}

    /**
     * Get Media Library
     *
     * Retrieves a paginated list of media files with comprehensive filtering, sorting,
     * and search capabilities. Users can filter by type, search by name, and sort by
     * various fields. Non-admin users will only see their own uploaded media.
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
     * Returns a paginated collection of media items with metadata including total count,
     * current page, per page limit, and pagination links.
     *
     * @response array{status: true, message: string, data: array{media: MediaResource[], meta: MetaResource}}
     */
    public function __invoke(GetMediaLibraryRequest $request): JsonResponse
    {
        try {
            $dto = FilterMediaDTO::fromRequest($request);

            // Apply user filtering based on permissions (non-managers see only their media)
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
