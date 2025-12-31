<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Admin\Comment;

use App\Enums\CommentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read string|null $search
 * @property-read string|null $status
 * @property-read int|null $user_id
 * @property-read int|null $article_id
 * @property-read string|null $sort_by
 * @property-read string|null $sort_order
 * @property-read int|null $per_page
 */
final class GetCommentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasPermission('comment_moderate');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(CommentStatus::class)],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'article_id' => ['nullable', 'integer', 'exists:articles,id'],
            'sort_by' => ['nullable', 'string', 'in:created_at,updated_at,content,user_id,article_id'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Get the default values for missing parameters
     *
     * @return array<string, mixed>
     */
    public function withDefaults(): array
    {
        return array_merge([
            'sort_by' => 'created_at',
            'sort_order' => 'desc',
            'per_page' => 15,
        ], $this->validated());
    }
}
