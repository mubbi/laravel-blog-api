<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Data\Media\FilterMediaDTO;
use App\Data\Media\UpdateMediaMetadataDTO;
use App\Data\Media\UploadMediaDTO;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Media service interface
 */
interface MediaServiceInterface
{
    /**
     * Upload a media file
     */
    public function uploadMedia(UploadedFile $file, UploadMediaDTO $dto): Media;

    /**
     * Update media metadata
     */
    public function updateMediaMetadata(int $id, UpdateMediaMetadataDTO $dto): Media;

    /**
     * Delete a media file
     */
    public function deleteMedia(int $id): bool;

    /**
     * Get media by ID
     */
    public function getMediaById(int $id): Media;

    /**
     * Get paginated media library
     *
     * @return LengthAwarePaginator<int, Media>
     */
    public function getMediaLibrary(FilterMediaDTO $dto): LengthAwarePaginator;
}
