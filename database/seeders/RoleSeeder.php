<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Role Seeder
 *
 * Creates the default roles based on the UserRole enum.
 * This seeder should be run before RolePermissionSeeder.
 */
final class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting role creation...');

        try {
            $createdCount = 0;
            $existingCount = 0;

            foreach (UserRole::cases() as $role) {
                $slug = strtolower(str_replace([' ', '_'], '-', $role->value));

                $existingRole = Role::where('name', $role->value)->first();

                if ($existingRole) {
                    $existingCount++;
                    $this->command->line("Role '{$role->value}' already exists");

                    continue;
                }

                Role::create([
                    'name' => $role->value,
                    'slug' => $slug,
                ]);

                $createdCount++;
                $this->command->info("Created role: {$role->value}");
            }

            $this->command->info("Role seeding completed. Created: {$createdCount}, Existing: {$existingCount}");
        } catch (\Throwable $e) {
            $this->command->error('Failed to create roles: '.$e->getMessage());
            throw $e;
        }
    }
}
