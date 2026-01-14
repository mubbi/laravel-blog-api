<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Log Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for log messages throughout
    | the application. These messages are primarily for internal logging
    | and debugging purposes.
    |
    */

    // General Log Messages
    'exception_occurred' => 'Exception occurred',
    'api_request' => 'API Request',

    // Exception Log Messages
    'model_not_found' => ':model not found',
    'authentication_failed' => 'Authentication failed',
    'authorization_failed' => 'Authorization failed',

    // User Log Messages
    'user_created' => 'User created',
    'user_updated' => 'User updated',
    'user_deleted' => 'User deleted',
    'user_banned' => 'User banned',
    'user_unbanned' => 'User unbanned',
    'user_blocked' => 'User blocked',
    'user_unblocked' => 'User unblocked',
    'user_logged_in' => 'User logged in',
    'user_logged_out' => 'User logged out',
    'token_refreshed' => 'Token refreshed',
    'user_followed' => 'User followed',
    'user_unfollowed' => 'User unfollowed',

    // User Permission Errors
    'has_permission_error' => 'hasPermission error',
    'has_any_permission_error' => 'hasAnyPermission error',
    'has_all_permissions_error' => 'hasAllPermissions error',

    // Article Log Messages
    'article_created' => 'Article created',
    'article_approved' => 'Article approved',
    'article_rejected' => 'Article rejected',
    'article_featured' => 'Article featured',
    'article_unfeatured' => 'Article unfeatured',
    'article_pinned' => 'Article pinned',
    'article_unpinned' => 'Article unpinned',
    'article_archived' => 'Article archived',
    'article_restored' => 'Article restored',
    'article_trashed' => 'Article trashed',
    'article_restored_from_trash' => 'Article restored from trash',
    'article_deleted' => 'Article deleted',
    'article_reported' => 'Article reported',
    'article_reports_cleared' => 'Article reports cleared',
    'article_liked' => 'Article liked',
    'article_disliked' => 'Article disliked',
    'feature_article_error' => 'FeatureArticle error',

    // Comment Log Messages
    'comment_created' => 'Comment created',
    'comment_updated' => 'Comment updated',
    'comment_approved' => 'Comment approved',
    'comment_deleted' => 'Comment deleted',
    'comment_reported' => 'Comment reported',

    // Newsletter Log Messages
    'newsletter_subscriber_deleted' => 'Newsletter subscriber deleted',

    // Category Log Messages
    'category_created' => 'Category created',
    'category_updated' => 'Category updated',
    'category_deleted' => 'Category deleted',

    // Tag Log Messages
    'tag_created' => 'Tag created',
    'tag_updated' => 'Tag updated',
    'tag_deleted' => 'Tag deleted',

    // Notification Log Messages
    'notification_created' => 'Notification created',
    'notification_sent' => 'Notification sent',
];
