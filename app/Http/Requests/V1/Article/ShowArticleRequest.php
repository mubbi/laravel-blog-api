<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Article;

use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;

final class ShowArticleRequest extends FormRequest
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
     * Check if user can view this specific article (for management)
     */
    public function canViewArticle(Article $article): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if (! $user->hasPermission('view_posts')) {
            return false;
        }

        // Admin users (with edit_others_posts permission) can view any article
        if ($user->hasPermission('edit_others_posts')) {
            return true;
        }

        // Regular users can only view their own articles
        return $user->id === $article->created_by;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // No additional validation rules needed for showing
        ];
    }
}
