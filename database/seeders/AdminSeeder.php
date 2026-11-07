<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@bookverse.test'],
            [
                'username' => 'admin',
                'password' => env('ADMIN_PASSWORD', 'change-me-please'),
                'role' => 'ADMIN',
                'status' => 'ACTIVE',
                'birth_date' => '1990-01-01',
                'email_verified_at' => now(),
            ]
        );
    }
}
