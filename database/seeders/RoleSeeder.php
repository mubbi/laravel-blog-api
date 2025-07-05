<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            'Administrator',
            'Editor',
            'Author',
            'Contributor',
            'Subscriber',
        ];

        foreach ($roles as $role) {
            $slug = strtolower(str_replace([' ', '_'], '-', $role));
            Role::firstOrCreate([
                'name' => $role,
                'slug' => $slug,
            ]);
        }
    }
}
