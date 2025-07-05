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
        return $user->hasPermission('edit_comments');
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->hasPermission('edit_comments');
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->hasPermission('delete_comments');
    }
}
