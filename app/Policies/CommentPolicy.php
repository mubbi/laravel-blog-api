<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    // Uses permission-based checks for authorization
    public function view(User $user, Comment $comment): bool
    {
        return $user->hasPermission('comment_moderate');
    }

    public function create(User $user): bool
    {
        // Any authenticated user can create comments
        return true;
    }

    public function update(User $user, Comment $comment): bool
    {
        // Admin can update any comment, users can only update their own
        if ($user->hasPermission('edit_comments')) {
            return true;
        }

        return $user->id === $comment->user_id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        // Admin can delete any comment, users can only delete their own
        if ($user->hasPermission('delete_comments')) {
            return true;
        }

        return $user->id === $comment->user_id;
    }

    public function report(User $user, Comment $comment): bool
    {
        // Any authenticated user can report comments
        return true;
    }
}
