<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Comment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read string $content
 * @property-read int|null $parent_comment_id
 */
final class CreateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only authenticated users can create comments
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:1', 'max:5000'],
            'parent_comment_id' => ['nullable', 'integer', 'exists:comments,id'],
        ];
    }

    /**
     * Get the default values for missing parameters
     *
     * @return array<string, mixed>
     */
    public function withDefaults(): array
    {
        return $this->validated();
    }
}
