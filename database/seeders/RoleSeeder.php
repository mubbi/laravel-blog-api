<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = UserRole::cases();

        foreach ($roles as $role) {
            $slug = strtolower(str_replace([' ', '_'], '-', $role->value));
            Role::firstOrCreate([
                'name' => $role->value,
                'slug' => $slug,
            ]);
        }
    }
}
