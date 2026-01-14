<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Admin\Article;

use App\Enums\ArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GetArticlesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasPermission('view_posts');
    }

    /**
     * Get the current user ID for filtering (non-admin users see only their articles)
     */
    public function getUserIdForFiltering(): ?int
    {
        $user = $this->user();

        if ($user === null) {
            return null;
        }

        // Admin users (with edit_others_posts permission) can see all articles
        if ($user->hasPermission('edit_others_posts')) {
            return null;
        }

        // Regular users can only see their own articles
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
            'search' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(ArticleStatus::class)],
            'author_id' => ['sometimes', 'integer', 'exists:users,id'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'tag_id' => ['sometimes', 'integer', 'exists:tags,id'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_pinned' => ['sometimes', 'boolean'],
            'has_reports' => ['sometimes', 'boolean'],
            'created_after' => ['sometimes', 'date'],
            'created_before' => ['sometimes', 'date'],
            'published_after' => ['sometimes', 'date'],
            'published_before' => ['sometimes', 'date'],
            'sort_by' => ['sometimes', 'string', 'in:title,created_at,published_at,status,is_featured,is_pinned,report_count'],
            'sort_direction' => ['sometimes', 'string', 'in:asc,desc'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
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
            'page' => 1,
            'per_page' => 15,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ], $this->validated());
    }
}
