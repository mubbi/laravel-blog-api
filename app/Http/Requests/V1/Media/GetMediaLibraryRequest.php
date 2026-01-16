<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Media;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read int|null $page
 * @property-read int|null $per_page
 * @property-read string|null $type
 * @property-read string|null $search
 * @property-read string|null $sort_by
 * @property-read string|null $sort_direction
 */
final class GetMediaLibraryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasPermission('view_media');
    }

    /**
     * Get the current user ID for filtering (non-manager users see only their media)
     */
    public function getUserIdForFiltering(): ?int
    {
        $user = $this->user();

        if ($user === null) {
            return null;
        }

        // Users with manage_media permission can see all media
        if ($user->hasPermission('manage_media')) {
            return null;
        }

        // Regular users can only see their own media
        return $user->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'type' => ['nullable', 'string', 'in:image,video,document,other'],
            'search' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', 'in:name,file_name,type,size,created_at,updated_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
