<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Media;

use App\Services\MediaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * @property-read \Illuminate\Http\UploadedFile $file
 * @property-read string|null $name
 * @property-read string|null $alt_text
 * @property-read string|null $caption
 * @property-read string|null $description
 * @property-read string|null $disk
 */
final class UploadMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasPermission('upload_media');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB
                File::types(MediaService::getAllowedFileExtensions()),
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:500'],
            'caption' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
            'disk' => ['nullable', 'string', 'in:public,local,s3'],
        ];
    }
}
