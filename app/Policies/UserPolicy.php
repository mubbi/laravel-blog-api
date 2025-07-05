<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    // Uses permission-based checks for authorization
    public function view(User $user, User $model): bool
    {
        return $user->hasPermission('view_users');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create_users');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermission('edit_users') || $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermission('delete_users');
    }
}
