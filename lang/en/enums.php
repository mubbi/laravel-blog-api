<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Enum Display Names Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for displaying enum values
    | in a user-friendly format throughout the application.
    |
    */

    // Article Status
    'article_status' => [
        'draft' => 'Draft',
        'review' => 'Under Review',
        'scheduled' => 'Scheduled',
        'published' => 'Published',
        'archived' => 'Archived',
        'trashed' => 'Trashed',
    ],

    // User Role
    'user_role' => [
        'administrator' => 'Administrator',
        'editor' => 'Editor',
        'author' => 'Author',
        'contributor' => 'Contributor',
        'subscriber' => 'Subscriber',
    ],

    // Comment Status
    'comment_status' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'spam' => 'Spam',
    ],

    // Article Author Role
    'article_author_role' => [
        'main' => 'Main Author',
        'co_author' => 'Co-Author',
        'contributor' => 'Contributor',
    ],

    // Notification Type
    'notification_type' => [
        'article_published' => 'Article Published',
        'new_comment' => 'New Comment',
        'newsletter' => 'Newsletter',
        'system_alert' => 'System Alert',
    ],
];
