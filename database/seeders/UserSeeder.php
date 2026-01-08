<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Use environment variables for admin credentials
        // Set ADMIN_EMAIL and ADMIN_PASSWORD in your .env file
        // For production, these MUST be set and should be strong credentials
        $email = env('ADMIN_EMAIL', 'admin@example-blog.com');
        $password = env('ADMIN_PASSWORD');

        // In production, require ADMIN_PASSWORD to be set
        if (app()->environment('production') && empty($password)) {
            throw new \RuntimeException(
                'ADMIN_PASSWORD environment variable must be set in production. '.
                'Please set a strong password in your .env file.'
            );
        }

        // For development, use a default password if not set
        if (empty($password)) {
            $password = ']v79£nKMHT74'; // Development default - CHANGE IN PRODUCTION!
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            $user = User::create([
                'name' => 'Administrator',
                'email' => $email,
                'password' => Hash::make($password),
            ]);
        }

        $role = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        if ($role && ! $user->roles()->where('role_id', $role->id)->exists()) {
            $user->roles()->attach($role->id);
        }
    }
}
