<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\NewsletterSubscriber;
use App\Models\User;

class NewsletterSubscriberPolicy
{
    // Uses permission-based checks for authorization
    public function view(User $user, NewsletterSubscriber $subscriber): bool
    {
        return $user->hasPermission('view_newsletter_subscribers');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manage_newsletter_subscribers') || $user->hasPermission('subscribe_newsletter');
    }

    public function update(User $user, NewsletterSubscriber $subscriber): bool
    {
        return $user->hasPermission('manage_newsletter_subscribers');
    }

    public function delete(User $user, NewsletterSubscriber $subscriber): bool
    {
        return $user->hasPermission('manage_newsletter_subscribers');
    }
}
