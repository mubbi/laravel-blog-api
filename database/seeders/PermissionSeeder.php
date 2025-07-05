<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // User & Account Management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'ban_users',
            'block_users',
            'restore_users',
            'assign_roles',
            'manage_roles',
            'manage_permissions',
            'edit_profile',
            'view_user_activity',

            // Article/Post Management
            'view_posts',
            'edit_posts',
            'delete_posts',
            'publish_posts',
            'edit_others_posts',
            'delete_others_posts',
            'approve_posts',
            'feature_posts',
            'pin_posts',
            'archive_posts',
            'restore_posts',
            'trash_posts',
            'report_posts',
            'like_posts',
            'dislike_posts',

            // Comment Management
            'comment_moderate',
            'edit_comments',
            'delete_comments',
            'approve_comments',
            'report_comments',

            // Taxonomy Management
            'manage_categories',
            'manage_tags',

            // Newsletter
            'view_newsletter_subscribers',
            'manage_newsletter_subscribers',
            'subscribe_newsletter',

            // Notifications
            'view_notifications',
            'manage_notifications',
            'send_notifications',

            // Media Management
            'upload_media',
            'delete_media',
            'manage_media',

            // Analytics & Settings
            'view_analytics',
            'manage_settings',

            // Social/Community
            'follow_users',

            // General
            'manage_options',
            'read',
        ];

        foreach ($permissions as $permission) {
            $slug = strtolower(str_replace([' ', '_'], '-', $permission));
            Permission::firstOrCreate([
                'name' => $permission,
                'slug' => $slug,
            ]);
        }
    }
}
