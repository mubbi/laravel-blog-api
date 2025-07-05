<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    // Uses permission-based checks for authorization
    public function view(User $user, Article $article): bool
    {
        return $user->hasPermission('view_posts');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('publish_posts');
    }

    public function update(User $user, Article $article): bool
    {
        // Can edit any article if has 'edit_others_posts', or own if has 'edit_posts'
        if ($user->hasPermission('edit_others_posts')) {
            return true;
        }

        return $user->hasPermission('edit_posts') && $user->id === $article->created_by;
    }

    public function delete(User $user, Article $article): bool
    {
        // Can delete any article if has 'delete_others_posts', or own if has 'delete_posts'
        if ($user->hasPermission('delete_others_posts')) {
            return true;
        }

        return $user->hasPermission('delete_posts') && $user->id === $article->created_by;
    }
}
