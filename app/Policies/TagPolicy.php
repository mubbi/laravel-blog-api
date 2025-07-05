<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    // Uses permission-based checks for authorization
    public function view(User $user, Tag $tag): bool
    {
        return $user->hasPermission('manage_tags');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manage_tags');
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->hasPermission('manage_tags');
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->hasPermission('manage_tags');
    }
}
