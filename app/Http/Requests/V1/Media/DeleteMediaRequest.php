<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Media;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $media = $this->route('media');

        if ($user === null || ! ($media instanceof Media)) {
            return false;
        }

        // Users with manage_media permission can delete any media
        if ($user->hasPermission('manage_media')) {
            return true;
        }

        // Users with delete_media permission can delete their own media
        return $user->hasPermission('delete_media')
            && $media->uploaded_by === $user->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
