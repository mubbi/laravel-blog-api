<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for notification messages
    |
    */

    'article_published' => [
        'title' => 'Article Published',
        'body' => 'Your article ":title" has been approved and published.',
    ],

    'article_rejected' => [
        'title' => 'Article Rejected',
        'body' => 'Your article ":title" has been rejected.',
    ],

    'new_comment' => [
        'title' => 'New Comment',
        'body' => ':commenter_name commented on your article ":article_title".',
    ],

    'comment_approved' => [
        'title' => 'Comment Approved',
        'body' => 'Your comment on ":article_title" has been approved.',
    ],

    'user_banned' => [
        'title' => 'Account Banned',
        'body' => 'Your account has been banned. Please contact support for more information.',
    ],

    'user_blocked' => [
        'title' => 'Account Blocked',
        'body' => 'Your account has been blocked. Please contact support for more information.',
    ],

    'user_followed' => [
        'title' => 'New Follower',
        'body' => ':follower_name started following you.',
    ],

    'article_reported' => [
        'title' => 'Article Reported',
        'body' => 'Article ":title" has been reported (:report_count time(s)). Please review.',
    ],

    'comment_reported' => [
        'title' => 'Comment Reported',
        'body' => 'A comment on article ":article_title" has been reported (:report_count time(s)). Please review.',
    ],
];
