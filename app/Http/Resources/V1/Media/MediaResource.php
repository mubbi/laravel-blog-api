<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Media;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Media $resource
 */
class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'file_name' => $this->resource->file_name,
            'mime_type' => $this->resource->mime_type,
            'disk' => $this->resource->disk,
            'path' => $this->resource->path,
            'url' => $this->resource->url,
            'size' => $this->resource->size,
            'size_human' => $this->formatBytes($this->resource->size),
            'type' => $this->resource->type,
            'alt_text' => $this->resource->alt_text,
            'caption' => $this->resource->caption,
            'description' => $this->resource->description,
            'metadata' => $this->resource->metadata,
            'created_at' => ($this->resource->created_at instanceof \DateTimeInterface ? $this->resource->created_at->toISOString() : $this->resource->created_at),
            'updated_at' => ($this->resource->updated_at instanceof \DateTimeInterface ? $this->resource->updated_at->toISOString() : $this->resource->updated_at),

            // Relationships
            'uploader' => $this->whenLoaded('uploader', function () {
                return $this->resource->uploader ? [
                    'id' => $this->resource->uploader->id,
                    'name' => $this->resource->uploader->name,
                    'email' => $this->resource->uploader->email,
                ] : null;
            }),
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round((float) $bytes, $precision).' '.$units[$i];
    }
}
