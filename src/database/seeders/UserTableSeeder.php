<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create an admin user
        User::create([
            'name' => 'Super Admin User',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password123'),
            'role_id' => 1, // Super Admin
        ]);

        // Create an admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role_id' => 2, // Admin
        ]);

        // Create a regular user
        User::create([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
            'password' => Hash::make('password123'),
            'role_id' => 3, // User
        ]);
    }
}
