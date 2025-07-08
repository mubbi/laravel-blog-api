<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        $rolePermissions = [
            UserRole::ADMINISTRATOR->value => [
                // User & Account Management
                'view_users', 'create_users', 'edit_users', 'delete_users', 'ban_users', 'block_users', 'restore_users',
                'assign_roles', 'manage_roles', 'manage_permissions', 'edit_profile', 'view_user_activity',
                // Article/Post Management
                'view_posts', 'edit_posts', 'delete_posts', 'publish_posts', 'edit_others_posts', 'delete_others_posts',
                'approve_posts', 'feature_posts', 'pin_posts', 'archive_posts', 'restore_posts', 'trash_posts', 'report_posts',
                'like_posts', 'dislike_posts',
                // Comment Management
                'comment_moderate', 'edit_comments', 'delete_comments', 'approve_comments', 'report_comments',
                // Taxonomy Management
                'manage_categories', 'manage_tags',
                // Newsletter
                'view_newsletter_subscribers', 'manage_newsletter_subscribers', 'subscribe_newsletter',
                // Notifications
                'view_notifications', 'manage_notifications', 'send_notifications',
                // Media Management
                'upload_media', 'delete_media', 'manage_media',
                // Analytics & Settings
                'view_analytics', 'manage_settings',
                // Social/Community
                'follow_users',
                // General
                'manage_options', 'read',
            ],
            UserRole::EDITOR->value => [
                // User & Account Management
                'view_users', 'edit_profile',
                // Article/Post Management
                'view_posts', 'edit_posts', 'delete_posts', 'publish_posts', 'edit_others_posts', 'delete_others_posts',
                'feature_posts', 'pin_posts', 'archive_posts', 'restore_posts', 'trash_posts', 'report_posts',
                'like_posts', 'dislike_posts',
                // Comment Management
                'comment_moderate', 'edit_comments', 'delete_comments', 'approve_comments', 'report_comments',
                // Taxonomy Management
                'manage_categories', 'manage_tags',
                // Newsletter
                'view_newsletter_subscribers', 'subscribe_newsletter',
                // Notifications
                'view_notifications',
                // Media Management
                'upload_media', 'delete_media', 'manage_media',
                // Analytics & Settings
                'view_analytics',
                // Social/Community
                'follow_users',
                // General
                'read',
            ],
            UserRole::AUTHOR->value => [
                // User & Account Management
                'edit_profile',
                // Article/Post Management
                'view_posts', 'edit_posts', 'delete_posts', 'publish_posts', 'archive_posts', 'restore_posts', 'trash_posts', 'report_posts',
                'like_posts', 'dislike_posts',
                // Comment Management
                'edit_comments', 'delete_comments', 'report_comments',
                // Newsletter
                'subscribe_newsletter',
                // Media Management
                'upload_media',
                // Social/Community
                'follow_users',
                // General
                'read',
            ],
            UserRole::CONTRIBUTOR->value => [
                // User & Account Management
                'edit_profile',
                // Article/Post Management
                'view_posts', 'edit_posts', 'delete_posts', 'trash_posts', 'report_posts', 'like_posts', 'dislike_posts',
                // Comment Management
                'edit_comments', 'delete_comments', 'report_comments',
                // Newsletter
                'subscribe_newsletter',
                // Media Management
                'upload_media',
                // Social/Community
                'follow_users',
                // General
                'read',
            ],
            UserRole::SUBSCRIBER->value => [
                // User & Account Management
                'edit_profile',
                // Article/Post Management
                'view_posts', 'report_posts', 'like_posts', 'dislike_posts',
                // Comment Management
                'report_comments',
                // Newsletter
                'subscribe_newsletter',
                // Social/Community
                'follow_users',
                // General
                'read',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $permissionIds = Permission::whereIn('name', $permissions)->pluck('id')->toArray();
                $role->permissions()->syncWithoutDetaching($permissionIds);
            }
        }
    }
}
