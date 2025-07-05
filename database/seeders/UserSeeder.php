<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $email = 'admin@example-blog.com';
        $user = User::where('email', $email)->first();
        if (! $user) {
            $user = User::create([
                'name' => 'Administrator',
                'email' => $email,
                'password' => Hash::make(']v79£nKMHT74'), // Change this in production!
            ]);
        }
        $role = Role::where('name', 'Administrator')->first();
        if ($role && ! $user->roles()->where('role_id', $role->id)->exists()) {
            $user->roles()->attach($role->id);
        }
    }
}
