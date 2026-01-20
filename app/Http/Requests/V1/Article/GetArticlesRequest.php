<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Article;

use App\Enums\ArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GetArticlesRequest extends FormRequest
{
    /**
     * Allow unauthenticated access - permissions checked in controller
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Check if user has view_posts permission
     */
    public function hasViewPostsPermission(): bool
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
        $baseRules = [
            'search' => ['sometimes', 'string', 'max:255'],
            'author_id' => ['sometimes', 'integer', 'exists:users,id'],
            'created_by' => ['sometimes', 'integer', 'exists:users,id'],
            'category_slug' => ['sometimes', 'string', 'exists:categories,slug'],
            'tag_slug' => ['sometimes', 'string', 'exists:tags,slug'],
            'published_after' => ['sometimes', 'date'],
            'published_before' => ['sometimes', 'date'],
            'sort_direction' => ['sometimes', 'string', 'in:asc,desc'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];

        // Management fields (require view_posts permission)
        if ($this->hasViewPostsPermission()) {
            return array_merge($baseRules, [
                'status' => ['sometimes', Rule::enum(ArticleStatus::class)],
                'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
                'tag_id' => ['sometimes', 'integer', 'exists:tags,id'],
                'is_featured' => ['sometimes', 'boolean'],
                'is_pinned' => ['sometimes', 'boolean'],
                'has_reports' => ['sometimes', 'boolean'],
                'created_after' => ['sometimes', 'date'],
                'created_before' => ['sometimes', 'date'],
                'sort_by' => ['sometimes', 'string', 'in:title,created_at,published_at,status,is_featured,is_pinned,report_count'],
            ]);
        }

        // Public rules - only published status allowed
        return array_merge($baseRules, [
            'status' => ['sometimes', 'string', Rule::in(['published'])],
            'sort_by' => ['sometimes', 'string', 'in:title,created_at,published_at'],
        ]);
    }

    /**
     * Get the default values for missing parameters
     *
     * @return array<string, mixed>
     */
    public function withDefaults(): array
    {
        $defaults = [
            'page' => 1,
            'per_page' => 15,
            'sort_direction' => 'desc',
        ];

        // Default sort_by based on permissions
        if ($this->hasViewPostsPermission()) {
            $defaults['sort_by'] = 'created_at';
        } else {
            $defaults['sort_by'] = 'published_at';
        }

        return array_merge($defaults, $this->validated());
    }
}
