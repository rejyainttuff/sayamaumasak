<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@baliculinary.test'],
            [
                'name' => 'Admin Bali Culinary',
                'role' => 'admin',
                'password' => 'password123',
            ]
        );

        User::updateOrCreate(
            ['email' => 'helper@baliculinary.test'],
            [
                'name' => 'Helper Bali Culinary',
                'role' => 'helper',
                'password' => 'password123',
            ]
        );

        User::updateOrCreate(
            ['email' => 'user@baliculinary.test'],
            [
                'name' => 'User Bali Culinary',
                'role' => 'user',
                'password' => 'password123',
            ]
        );
    }
}
