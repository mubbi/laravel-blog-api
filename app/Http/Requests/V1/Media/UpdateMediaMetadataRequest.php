<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Media;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read string|null $name
 * @property-read string|null $alt_text
 * @property-read string|null $caption
 * @property-read string|null $description
 */
final class UpdateMediaMetadataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $media = $this->route('media');

        if ($user === null || ! ($media instanceof \App\Models\Media)) {
            return false;
        }

        // Users with manage_media permission can update any media
        if ($user->hasPermission('manage_media')) {
            return true;
        }

        // Users with edit_media permission can update their own media
        return $user->hasPermission('edit_media')
            && $media->uploaded_by === $user->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:500'],
            'caption' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
