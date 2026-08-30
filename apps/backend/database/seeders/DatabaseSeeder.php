<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => env('APP_ADMIN_EMAIL', 'admin@family.local'),
            'password' => bcrypt('password'),
            'status' => 'approved',
            'role' => 'admin',
        ]);
    }
}
