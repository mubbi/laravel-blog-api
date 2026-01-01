<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Admin\Comment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read string|null $reason
 */
final class DeleteCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasPermission('delete_comments');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
