<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    // Uses permission-based checks for authorization
    public function view(User $user, Category $category): bool
    {
        return $user->hasPermission('manage_categories');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manage_categories');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasPermission('manage_categories');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->hasPermission('manage_categories');
    }
}
