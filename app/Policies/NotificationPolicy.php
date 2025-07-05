<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    // Uses permission-based checks for authorization
    public function view(User $user, Notification $notification): bool
    {
        return $user->hasPermission('view_notifications');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('send_notifications');
    }

    public function update(User $user, Notification $notification): bool
    {
        return $user->hasPermission('manage_notifications');
    }

    public function delete(User $user, Notification $notification): bool
    {
        return $user->hasPermission('manage_notifications');
    }
}
