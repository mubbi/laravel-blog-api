<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * Permission Seeder
 *
 * Creates all the permissions used in the application.
 * This seeder should be run before RoleSeeder and RolePermissionSeeder.
 */
final class PermissionSeeder extends Seeder
{
    /**
     * All permissions in the system
     *
     * @var array<int, string>
     */
    private const PERMISSIONS = [
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
        'register_user',
        'view_own_profile',

        // Article/Post Management
        'view_posts',
        'create_posts',
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
        'view_own_posts',
        'schedule_posts',

        // Comment Management
        'comment_moderate',
        'create_comments',
        'edit_comments',
        'delete_comments',
        'approve_comments',
        'report_comments',
        'view_comments',
        'edit_own_comments',
        'delete_own_comments',

        // Taxonomy Management
        'manage_categories',
        'create_categories',
        'edit_categories',
        'delete_categories',
        'view_categories',
        'manage_tags',
        'create_tags',
        'edit_tags',
        'delete_tags',
        'view_tags',

        // Newsletter
        'view_newsletter_subscribers',
        'manage_newsletter_subscribers',
        'subscribe_newsletter',
        'unsubscribe_newsletter',
        'send_newsletter',

        // Notifications
        'view_notifications',
        'manage_notifications',
        'send_notifications',
        'read_notifications',
        'delete_notifications',

        // Media Management
        'upload_media',
        'delete_media',
        'manage_media',
        'view_media',
        'edit_media',

        // Analytics & Settings
        'view_analytics',
        'manage_settings',
        'view_dashboard',
        'export_data',

        // Social/Community
        'follow_users',
        'unfollow_users',
        'view_user_profiles',
        'send_messages',

        // General
        'manage_options',
        'read',
        'access_api',
        'view_logs',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting permission creation...');

        try {
            $createdCount = 0;
            $existingCount = 0;

            foreach (self::PERMISSIONS as $permission) {
                $slug = strtolower(str_replace([' ', '_'], '-', $permission));

                $existingPermission = Permission::where('name', $permission)->first();

                if ($existingPermission) {
                    $existingCount++;
                    $this->command->line("Permission '{$permission}' already exists");

                    continue;
                }

                Permission::create([
                    'name' => $permission,
                    'slug' => $slug,
                ]);

                $createdCount++;
                $this->command->info("Created permission: {$permission}");
            }

            $this->command->info("Permission seeding completed. Created: {$createdCount}, Existing: {$existingCount}");

        } catch (\Throwable $e) {
            $this->command->error('Failed to create permissions: '.$e->getMessage());
            throw $e;
        }
    }
}
