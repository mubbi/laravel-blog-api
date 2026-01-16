<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Media;

use App\Data\Media\UploadMediaDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Media\UploadMediaRequest;
use App\Http\Resources\V1\Media\MediaResource;
use App\Services\Interfaces\MediaServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Media Management', weight: 3)]
final class UploadMediaController extends Controller
{
    public function __construct(
        private readonly MediaServiceInterface $mediaService
    ) {}

    /**
     * Upload Media File
     *
     * Uploads a media file (image, video, or document) to the server. The file will be stored
     * in the configured storage disk. Metadata such as dimensions (for images) will be
     * automatically extracted and stored.
     *
     * **Access Control:**
     * - **Authenticated users** with `upload_media` permission can upload files
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `upload_media` permission.
     *
     * **Request Body (multipart/form-data):**
     * - `file` (required, file): The media file to upload (max 10MB)
     *   - Supported image types: jpg, jpeg, png, gif, webp, svg
     *   - Supported video types: mp4, mpeg, mov, avi, webm
     *   - Supported document types: pdf, doc, docx, xls, xlsx, txt
     * - `name` (optional, string, max:255): Custom name for the media
     * - `alt_text` (optional, string, max:500): Alt text for accessibility
     * - `caption` (optional, string, max:500): Caption for the media
     * - `description` (optional, string, max:1000): Description of the media
     * - `disk` (optional, string, in:public,local,s3): Storage disk to use (default: public)
     *
     * **Response:**
     * Returns the uploaded media object with all details including URL, metadata, and relationships.
     * The response includes HTTP 201 Created status code.
     *
     * @response array{status: true, message: string, data: MediaResource}
     */
    public function __invoke(UploadMediaRequest $request): JsonResponse
    {
        try {
            $dto = UploadMediaDTO::fromRequest($request);
            $file = $request->file('file');
            $media = $this->mediaService->uploadMedia($file, $dto);

            return response()->apiSuccess(
                new MediaResource($media),
                __('common.media_uploaded_successfully'),
                Response::HTTP_CREATED
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
