<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Comment;

use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read string $content
 */
final class UpdateCommentRequest extends FormRequest
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

        // Admin can update any comment, users can only update their own
        if ($user->hasPermission('edit_comments')) {
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
            'content' => ['required', 'string', 'min:1', 'max:5000'],
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
