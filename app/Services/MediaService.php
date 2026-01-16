<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\FilterMediaDTO;
use App\Data\UpdateMediaMetadataDTO;
use App\Data\UploadMediaDTO;
use App\Events\Media\MediaDeletedEvent;
use App\Events\Media\MediaMetadataUpdatedEvent;
use App\Events\Media\MediaUploadedEvent;
use App\Models\Media;
use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Services\Interfaces\MediaServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class MediaService implements MediaServiceInterface
{
    /**
     * Allowed image MIME types
     *
     * @var array<string>
     */
    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
    ];

    /**
     * Allowed video MIME types
     *
     * @var array<string>
     */
    private const ALLOWED_VIDEO_TYPES = [
        'video/mp4',
        'video/mpeg',
        'video/quicktime',
        'video/x-msvideo',
        'video/webm',
    ];

    /**
     * Allowed document MIME types
     *
     * @var array<string>
     */
    private const ALLOWED_DOCUMENT_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
    ];

    /**
     * Allowed image file extensions
     *
     * @var array<string>
     */
    public const ALLOWED_IMAGE_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'svg',
    ];

    /**
     * Allowed video file extensions
     *
     * @var array<string>
     */
    public const ALLOWED_VIDEO_EXTENSIONS = [
        'mp4',
        'mpeg',
        'mov',
        'avi',
        'webm',
    ];

    /**
     * Allowed document file extensions
     *
     * @var array<string>
     */
    public const ALLOWED_DOCUMENT_EXTENSIONS = [
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'txt',
    ];

    /**
     * Maximum file size in bytes (10MB)
     */
    private const MAX_FILE_SIZE = 10485760;

    /**
     * Get all allowed file extensions
     *
     * @return array<int, string>
     */
    public static function getAllowedFileExtensions(): array
    {
        return array_values(array_merge(
            self::ALLOWED_IMAGE_EXTENSIONS,
            self::ALLOWED_VIDEO_EXTENSIONS,
            self::ALLOWED_DOCUMENT_EXTENSIONS
        ));
    }

    public function __construct(
        private readonly MediaRepositoryInterface $mediaRepository
    ) {}

    /**
     * Upload a media file
     */
    public function uploadMedia(UploadedFile $file, UploadMediaDTO $dto): Media
    {
        // Validate file (includes MIME type check)
        $this->validateFile($file);

        // Determine media type (MIME type is guaranteed to be non-null after validateFile())
        $mimeType = $file->getMimeType();
        assert($mimeType !== null);
        $type = $this->determineMediaType($mimeType);

        // Generate unique file name
        $fileName = $this->generateFileName($file);

        // Store file
        $path = $file->storeAs('media/'.date('Y/m'), $fileName, $dto->disk);

        if ($path === false) {
            throw new \RuntimeException(__('common.failed_to_upload_file'));
        }

        // Get file URL
        $url = Storage::disk($dto->disk)->url($path);

        // Get file metadata
        $metadata = $this->extractMetadata($file, $type);

        // Prepare media data
        $mediaData = [
            'name' => $dto->name ?? $file->getClientOriginalName(),
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'disk' => $dto->disk,
            'path' => $path,
            'url' => $url,
            'size' => $file->getSize(),
            'type' => $type,
            'alt_text' => $dto->altText,
            'caption' => $dto->caption,
            'description' => $dto->description,
            'metadata' => $metadata,
            'uploaded_by' => $dto->uploadedBy,
        ];

        // Create media record in transaction
        $media = DB::transaction(function () use ($mediaData): Media {
            return $this->mediaRepository->create($mediaData);
        });

        // Dispatch event after transaction commits
        Event::dispatch(new MediaUploadedEvent($media));

        return $media;
    }

    /**
     * Update media metadata
     */
    public function updateMediaMetadata(int $id, UpdateMediaMetadataDTO $dto): Media
    {
        $updateData = $dto->toArray();

        if ($updateData !== []) {
            $this->mediaRepository->update($id, $updateData);
        }

        $media = $this->mediaRepository->findOrFail($id);

        // Dispatch event after media metadata is updated
        Event::dispatch(new MediaMetadataUpdatedEvent($media));

        return $media;
    }

    /**
     * Delete a media file
     */
    public function deleteMedia(int $id): bool
    {
        // Get media before deletion for event
        $media = $this->mediaRepository->findOrFail($id);

        // Delete file and database record in transaction
        $deleted = DB::transaction(function () use ($id, $media): bool {
            // Delete file from storage
            if (Storage::disk($media->disk)->exists($media->path)) {
                Storage::disk($media->disk)->delete($media->path);
            }

            // Delete media record
            return $this->mediaRepository->delete($id);
        });

        // Dispatch event after transaction commits
        if ($deleted) {
            Event::dispatch(new MediaDeletedEvent($media));
        }

        return $deleted;
    }

    /**
     * Get media by ID
     */
    public function getMediaById(int $id): Media
    {
        return $this->mediaRepository->query()
            ->with('uploader:id,name,email')
            ->findOrFail($id);
    }

    /**
     * Get paginated media library
     *
     * @return LengthAwarePaginator<int, Media>
     */
    public function getMediaLibrary(FilterMediaDTO $dto): LengthAwarePaginator
    {
        $query = $this->mediaRepository->query()
            ->with('uploader:id,name,email');

        // Filter by type
        if ($dto->type !== null) {
            $query->where('type', $dto->type);
        }

        // Filter by uploaded_by
        if ($dto->uploadedBy !== null) {
            $query->where('uploaded_by', $dto->uploadedBy);
        }

        // Search by name
        if ($dto->search !== null) {
            $query->where(function ($q) use ($dto): void {
                $q->where('name', 'like', "%{$dto->search}%")
                    ->orWhere('file_name', 'like', "%{$dto->search}%")
                    ->orWhere('alt_text', 'like', "%{$dto->search}%");
            });
        }

        // Sort
        $query->orderBy($dto->sortBy, $dto->sortDirection);

        // Paginate
        return $query->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }

    /**
     * Validate uploaded file
     *
     * @throws \InvalidArgumentException
     */
    private function validateFile(UploadedFile $file): void
    {
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        if ($size === false || $size > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException(__('common.file_size_exceeds_limit'));
        }

        $allowedTypes = array_merge(
            self::ALLOWED_IMAGE_TYPES,
            self::ALLOWED_VIDEO_TYPES,
            self::ALLOWED_DOCUMENT_TYPES
        );

        if (! in_array($mimeType, $allowedTypes, true)) {
            throw new \InvalidArgumentException(__('common.invalid_file_type'));
        }
    }

    /**
     * Determine media type from MIME type
     */
    private function determineMediaType(string $mimeType): string
    {
        if (in_array($mimeType, self::ALLOWED_IMAGE_TYPES, true)) {
            return 'image';
        }

        if (in_array($mimeType, self::ALLOWED_VIDEO_TYPES, true)) {
            return 'video';
        }

        if (in_array($mimeType, self::ALLOWED_DOCUMENT_TYPES, true)) {
            return 'document';
        }

        return 'other';
    }

    /**
     * Generate unique file name
     */
    private function generateFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitizedBaseName = Str::slug($baseName);
        $timestamp = now()->timestamp;
        $random = Str::random(8);

        return "{$sanitizedBaseName}-{$timestamp}-{$random}.{$extension}";
    }

    /**
     * Extract metadata from file
     *
     * @return array<string, mixed>
     */
    private function extractMetadata(UploadedFile $file, string $type): array
    {
        $metadata = [];

        if ($type === 'image') {
            try {
                $imageInfo = getimagesize($file->getRealPath());
                if ($imageInfo !== false) {
                    $metadata['width'] = $imageInfo[0];
                    $metadata['height'] = $imageInfo[1];
                    $metadata['dimensions'] = "{$imageInfo[0]}x{$imageInfo[1]}";
                }
            } catch (\Throwable $e) {
                // Ignore errors in metadata extraction
            }
        }

        return $metadata;
    }
}
