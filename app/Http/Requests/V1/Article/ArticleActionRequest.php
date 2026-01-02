<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Article;

use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base request class for article actions with optimized authorization
 */
abstract class ArticleActionRequest extends FormRequest
{
    /**
     * Check if user can perform action on article
     * Uses route model binding to get article, avoiding unnecessary queries
     */
    protected function canPerformAction(string $adminPermission, string $ownPermission): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        // Admin users with admin permission can perform action on any article
        if ($user->hasPermission($adminPermission)) {
            return true;
        }

        // Get article from route model binding
        $article = $this->route('article');

        if (! $article instanceof Article) {
            return false;
        }

        // Users can perform action on their own articles if they have own permission
        return $user->hasPermission($ownPermission)
            && $user->id === $article->created_by;
    }

    /**
     * Get the article from route model binding
     */
    protected function getArticle(): ?Article
    {
        $article = $this->route('article');

        return $article instanceof Article ? $article : null;
    }
}
