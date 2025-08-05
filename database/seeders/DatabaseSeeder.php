<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed permissions first (required for roles)
        $this->call([
            PermissionSeeder::class,
        ]);

        // Seed roles and assign permissions
        $this->call([
            RoleSeeder::class,
            RolePermissionSeeder::class,
        ]);

        // Seed other data
        $this->call([
            UserSeeder::class,
        ]);
    }
}
