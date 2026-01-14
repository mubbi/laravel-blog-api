<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Common Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are shared for common responses
    |
    */

    // General Messages
    'something_went_wrong' => 'Something went wrong! Try again later.',
    'success' => 'Response returned successfully.',
    'error' => 'An error occurred while processing your request.',
    'not_found' => 'Resource not found.',
    'unauthorized' => 'You are not authorized to perform this action.',
    'forbidden' => 'Access denied.',
    'validation_failed' => 'The provided data is invalid.',
    'unexpected_response_format' => 'Unexpected response format from resource collection.',

    // User Management
    'user_not_found' => 'User not found.',
    'user_created_successfully' => 'User created successfully.',
    'user_updated_successfully' => 'User updated successfully.',
    'user_deleted_successfully' => 'User deleted successfully.',
    'user_banned_successfully' => 'User banned successfully.',
    'user_unbanned_successfully' => 'User unbanned successfully.',
    'user_blocked_successfully' => 'User blocked successfully.',
    'user_unblocked_successfully' => 'User unblocked successfully.',
    'profile_updated_successfully' => 'Profile updated successfully.',
    'cannot_delete_self' => 'You cannot delete your own account.',
    'cannot_ban_self' => 'You cannot ban your own account.',
    'cannot_unban_self' => 'You cannot unban your own account.',
    'cannot_block_self' => 'You cannot block your own account.',
    'cannot_unblock_self' => 'You cannot unblock your own account.',
    'cannot_follow_self' => 'You cannot follow yourself.',
    'cannot_unfollow_self' => 'You cannot unfollow yourself.',

    // Article Management
    'article_not_found' => 'Article not found.',
    'article_created_successfully' => 'Article created successfully.',
    'article_updated_successfully' => 'Article updated successfully.',
    'article_deleted_successfully' => 'Article deleted successfully.',
    'article_approved_successfully' => 'Article approved successfully.',
    'article_rejected_successfully' => 'Article rejected successfully.',
    'article_featured_successfully' => 'Article featured successfully.',
    'article_unfeatured_successfully' => 'Article unfeatured successfully.',
    'article_pinned_successfully' => 'Article pinned successfully.',
    'article_unpinned_successfully' => 'Article unpinned successfully.',
    'article_archived_successfully' => 'Article archived successfully.',
    'article_restored_successfully' => 'Article restored successfully.',
    'article_trashed_successfully' => 'Article moved to trash successfully.',
    'article_restored_from_trash_successfully' => 'Article restored from trash successfully.',
    'article_reported_successfully' => 'Article reported successfully.',
    'article_reports_cleared_successfully' => 'Article reports cleared successfully.',
    'no_reason_provided' => 'No reason provided',

    // Comment Management
    'comment_not_found' => 'Comment not found.',
    'comment_created_successfully' => 'Comment created successfully.',
    'comment_updated_successfully' => 'Comment updated successfully.',
    'comment_deleted_successfully' => 'Comment deleted successfully.',
    'comment_approved_successfully' => 'Comment approved successfully.',
    'comment_rejected_successfully' => 'Comment rejected successfully.',
    'comment_reported_successfully' => 'Comment reported successfully.',
    'comment_reports_cleared_successfully' => 'Comment reports cleared successfully.',
    'parent_comment_mismatch' => 'Parent comment must belong to the same article.',
    // Additional comment keys for consistency
    'comment_deleted' => 'Comment deleted successfully.',
    'comment_approved' => 'Comment approved successfully.',

    // Category Management
    'category_not_found' => 'Category not found.',
    'category_created_successfully' => 'Category created successfully.',
    'category_updated_successfully' => 'Category updated successfully.',
    'category_deleted_successfully' => 'Category deleted successfully.',

    // Tag Management
    'tag_not_found' => 'Tag not found.',
    'tag_created_successfully' => 'Tag created successfully.',
    'tag_updated_successfully' => 'Tag updated successfully.',
    'tag_deleted_successfully' => 'Tag deleted successfully.',

    // Newsletter Management
    'subscriber_not_found' => 'Newsletter subscriber not found.',
    'subscriber_created_successfully' => 'Newsletter subscriber created successfully.',
    'subscriber_updated_successfully' => 'Newsletter subscriber updated successfully.',
    'subscriber_deleted_successfully' => 'Newsletter subscriber deleted successfully.',
    'subscriber_verified_successfully' => 'Newsletter subscriber verified successfully.',
    'subscriber_unsubscribed_successfully' => 'Newsletter subscriber unsubscribed successfully.',
    // Additional subscriber keys for consistency
    'subscriber_deleted' => 'Newsletter subscriber deleted successfully.',

    // Notification Management
    'notification_not_found' => 'Notification not found.',
    'notification_created_successfully' => 'Notification created successfully.',
    'notification_updated_successfully' => 'Notification updated successfully.',
    'notification_deleted_successfully' => 'Notification deleted successfully.',
    'notification_sent_successfully' => 'Notification sent successfully.',
    // Additional notification keys for consistency
    'notification_created' => 'Notification created successfully.',

    // Role Management
    'role_not_found' => 'Role not found.',
    'role_created_successfully' => 'Role created successfully.',
    'role_updated_successfully' => 'Role updated successfully.',
    'role_deleted_successfully' => 'Role deleted successfully.',

    // Permission Management
    'permission_not_found' => 'Permission not found.',
    'permission_created_successfully' => 'Permission created successfully.',
    'permission_updated_successfully' => 'Permission updated successfully.',
    'permission_deleted_successfully' => 'Permission deleted successfully.',

    // System Messages
    'database_connection_failed' => 'Database connection failed.',
    'cache_cleared_successfully' => 'Cache cleared successfully.',
    'maintenance_mode_enabled' => 'Maintenance mode enabled.',
    'maintenance_mode_disabled' => 'Maintenance mode disabled.',
    'backup_created_successfully' => 'Backup created successfully.',
    'backup_restored_successfully' => 'Backup restored successfully.',

    // Pagination
    'no_more_records' => 'No more records available.',
    'records_per_page' => 'Records per page',
    'showing_from_to_of' => 'Showing :from to :to of :total records',

    // Search
    'search_no_results' => 'No results found for your search.',
    'search_results_found' => ':count results found for your search.',

    // File Operations
    'file_uploaded_successfully' => 'File uploaded successfully.',
    'file_deleted_successfully' => 'File deleted successfully.',
    'file_not_found' => 'File not found.',
    'file_too_large' => 'File size exceeds the maximum allowed limit.',
    'invalid_file_type' => 'Invalid file type. Allowed types: :allowed_types',

    // Import/Export
    'import_started_successfully' => 'Import process started successfully.',
    'import_completed_successfully' => 'Import completed successfully.',
    'import_failed' => 'Import failed. Please check your file and try again.',
    'export_started_successfully' => 'Export process started successfully.',
    'export_completed_successfully' => 'Export completed successfully.',
    'export_failed' => 'Export failed. Please try again.',

    // Bulk Operations
    'bulk_operation_started' => 'Bulk operation started successfully.',
    'bulk_operation_completed' => 'Bulk operation completed successfully.',
    'bulk_operation_failed' => 'Bulk operation failed. Please try again.',
    'items_selected' => ':count items selected.',
    'no_items_selected' => 'No items selected for bulk operation.',

    // Status Messages
    'status_updated_successfully' => 'Status updated successfully.',
    'status_change_failed' => 'Failed to update status. Please try again.',
    'status_invalid' => 'Invalid status provided.',

    // Authentication & Authorization
    'login_required' => 'Please log in to access this resource.',
    'permission_denied' => 'You do not have permission to perform this action.',
    'unauthorized_token' => 'Unauthorized. Invalid token or insufficient permissions.',
    'session_expired' => 'Your session has expired. Please log in again.',
    'account_locked' => 'Your account has been locked. Please contact support.',
    'account_suspended' => 'Your account has been suspended. Please contact support.',

    // Rate Limiting
    'too_many_requests' => 'Too many requests. Please try again later.',
    'rate_limit_exceeded' => 'Rate limit exceeded. Please slow down your requests.',

    // API Specific
    'api_version_deprecated' => 'This API version is deprecated. Please upgrade to the latest version.',
    'api_version_unsupported' => 'This API version is not supported.',
    'api_key_invalid' => 'Invalid API key provided.',
    'api_key_expired' => 'API key has expired.',
    'api_key_missing' => 'API key is required for this endpoint.',

    // Webhook
    'webhook_sent_successfully' => 'Webhook sent successfully.',
    'webhook_failed' => 'Webhook delivery failed.',
    'webhook_invalid_signature' => 'Invalid webhook signature.',

    // Queue & Jobs
    'job_queued_successfully' => 'Job queued successfully.',
    'job_processing' => 'Job is being processed.',
    'job_completed_successfully' => 'Job completed successfully.',
    'job_failed' => 'Job failed. Please check the logs for details.',
    'job_cancelled' => 'Job cancelled successfully.',

    // Logs
    'logs_cleared_successfully' => 'Logs cleared successfully.',
    'logs_exported_successfully' => 'Logs exported successfully.',
    'logs_not_found' => 'No logs found for the specified criteria.',

    // Health Check
    'system_healthy' => 'System is healthy and running normally.',
    'system_degraded' => 'System is experiencing issues.',
    'system_unhealthy' => 'System is unhealthy and requires attention.',
];
