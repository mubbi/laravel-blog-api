<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Comment;

use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        $comment = $this->route('comment');
        if (! $comment instanceof Comment) {
            return false;
        }

        // Admin can delete any comment, users can only delete their own
        if ($user->hasPermission('delete_comments')) {
            return true;
        }

        return $user->id === $comment->user_id;
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
