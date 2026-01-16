<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Role Permission Seeder
 *
 * Assigns permissions to roles based on the defined role hierarchy.
 * This seeder should be run after PermissionSeeder and RoleSeeder.
 */
final class RolePermissionSeeder extends Seeder
{
    /**
     * Role permissions mapping
     *
     * @var array<string, array<int, string>>
     */
    private const ROLE_PERMISSIONS = [
        UserRole::ADMINISTRATOR->value => [
            // User & Account Management
            'view_users', 'create_users', 'edit_users', 'delete_users', 'ban_users', 'block_users', 'restore_users',
            'assign_roles', 'manage_roles', 'manage_permissions', 'edit_profile', 'view_user_activity',
            'register_user', 'view_own_profile',
            // Article/Post Management
            'view_posts', 'create_posts', 'edit_posts', 'delete_posts', 'publish_posts', 'edit_others_posts', 'delete_others_posts',
            'approve_posts', 'feature_posts', 'pin_posts', 'archive_posts', 'restore_posts', 'trash_posts', 'report_posts',
            'like_posts', 'dislike_posts', 'view_own_posts', 'schedule_posts',
            // Comment Management
            'comment_moderate', 'create_comments', 'edit_comments', 'delete_comments', 'approve_comments', 'report_comments',
            'view_comments', 'edit_own_comments', 'delete_own_comments',
            // Taxonomy Management
            'manage_categories', 'create_categories', 'edit_categories', 'delete_categories', 'view_categories',
            'manage_tags', 'create_tags', 'edit_tags', 'delete_tags', 'view_tags',
            // Newsletter
            'view_newsletter_subscribers', 'manage_newsletter_subscribers', 'subscribe_newsletter',
            'unsubscribe_newsletter', 'send_newsletter',
            // Notifications
            'view_notifications', 'manage_notifications', 'send_notifications',
            'read_notifications', 'delete_notifications',
            // Media Management
            'upload_media', 'delete_media', 'manage_media', 'view_media', 'edit_media',
            // Analytics & Settings
            'view_analytics', 'manage_settings', 'view_dashboard', 'export_data',
            // Social/Community
            'follow_users', 'unfollow_users', 'view_user_profiles', 'send_messages',
            // General
            'manage_options', 'read', 'access_api', 'view_logs',
        ],
        UserRole::EDITOR->value => [
            // User & Account Management
            'view_users', 'edit_profile', 'view_own_profile',
            // Article/Post Management
            'view_posts', 'create_posts', 'edit_posts', 'delete_posts', 'publish_posts', 'edit_others_posts', 'delete_others_posts',
            'feature_posts', 'pin_posts', 'archive_posts', 'restore_posts', 'trash_posts', 'report_posts',
            'like_posts', 'dislike_posts', 'view_own_posts', 'schedule_posts',
            // Comment Management
            'comment_moderate', 'create_comments', 'edit_comments', 'delete_comments', 'approve_comments', 'report_comments',
            'view_comments', 'edit_own_comments', 'delete_own_comments',
            // Taxonomy Management
            'manage_categories', 'create_categories', 'edit_categories', 'delete_categories', 'view_categories',
            'manage_tags', 'create_tags', 'edit_tags', 'delete_tags', 'view_tags',
            // Newsletter
            'view_newsletter_subscribers', 'subscribe_newsletter', 'unsubscribe_newsletter',
            // Notifications
            'view_notifications', 'read_notifications',
            // Media Management
            'upload_media', 'delete_media', 'manage_media', 'view_media', 'edit_media',
            // Analytics & Settings
            'view_analytics', 'view_dashboard',
            // Social/Community
            'follow_users', 'unfollow_users', 'view_user_profiles',
            // General
            'read', 'access_api',
        ],
        UserRole::AUTHOR->value => [
            // User & Account Management
            'edit_profile', 'view_own_profile',
            // Article/Post Management
            'view_posts', 'create_posts', 'edit_posts', 'delete_posts', 'publish_posts', 'archive_posts', 'restore_posts', 'trash_posts', 'report_posts',
            'like_posts', 'dislike_posts', 'view_own_posts', 'schedule_posts',
            // Comment Management
            'create_comments', 'edit_comments', 'delete_comments', 'report_comments',
            'view_comments', 'edit_own_comments', 'delete_own_comments',
            // Newsletter
            'subscribe_newsletter', 'unsubscribe_newsletter',
            // Media Management
            'upload_media', 'view_media', 'delete_media', 'edit_media',
            // Social/Community
            'follow_users', 'unfollow_users', 'view_user_profiles',
            // General
            'read', 'access_api',
        ],
        UserRole::CONTRIBUTOR->value => [
            // User & Account Management
            'edit_profile', 'view_own_profile',
            // Article/Post Management
            'view_posts', 'create_posts', 'edit_posts', 'delete_posts', 'trash_posts', 'report_posts', 'like_posts', 'dislike_posts',
            'view_own_posts',
            // Comment Management
            'create_comments', 'edit_comments', 'delete_comments', 'report_comments',
            'view_comments', 'edit_own_comments', 'delete_own_comments',
            // Newsletter
            'subscribe_newsletter', 'unsubscribe_newsletter',
            // Media Management
            'upload_media', 'view_media',
            // Social/Community
            'follow_users', 'unfollow_users', 'view_user_profiles',
            // General
            'read', 'access_api',
        ],
        UserRole::SUBSCRIBER->value => [
            // User & Account Management
            'edit_profile', 'view_own_profile',
            // Article/Post Management
            'view_posts', 'report_posts', 'like_posts', 'dislike_posts',
            // Comment Management
            'create_comments', 'report_comments', 'view_comments', 'edit_own_comments', 'delete_own_comments',
            // Newsletter
            'subscribe_newsletter', 'unsubscribe_newsletter',
            // Social/Community
            'follow_users', 'unfollow_users', 'view_user_profiles',
            // General
            'read', 'access_api',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting role permission assignment...');

        try {
            DB::beginTransaction();

            // Pre-load all permissions for efficiency
            $permissions = Permission::all()->keyBy('name');
            $roles = Role::all()->keyBy('name');

            $this->command->info('Found '.$permissions->count().' permissions and '.$roles->count().' roles');

            $assignedCount = 0;
            $missingPermissions = [];
            $missingRoles = [];

            foreach (self::ROLE_PERMISSIONS as $roleName => $permissionNames) {
                $role = $roles->get($roleName);

                if (! $role) {
                    $missingRoles[] = $roleName;

                    continue;
                }

                $permissionIds = [];
                foreach ($permissionNames as $permissionName) {
                    $permission = $permissions->get($permissionName);

                    if (! $permission) {
                        $missingPermissions[] = $permissionName;

                        continue;
                    }

                    $permissionIds[] = $permission->id;
                }

                if (! empty($permissionIds)) {
                    // Use sync instead of syncWithoutDetaching for clean assignment
                    $role->permissions()->sync($permissionIds);
                    $assignedCount += count($permissionIds);

                    $this->command->info('Assigned '.count($permissionIds)." permissions to role: {$roleName}");
                }
            }

            if (! empty($missingRoles)) {
                $this->command->warn('Missing roles: '.implode(', ', array_unique($missingRoles)));
            }

            if (! empty($missingPermissions)) {
                $this->command->warn('Missing permissions: '.implode(', ', array_unique($missingPermissions)));
            }

            DB::commit();
            $this->command->info("Successfully assigned {$assignedCount} permission-role relationships");
        } catch (Throwable $e) {
            DB::rollBack();
            $this->command->error('Failed to assign role permissions: '.$e->getMessage());
            throw $e;
        }
    }
}
