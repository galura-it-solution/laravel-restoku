<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@restoku.test'],
            [
                'name' => 'Restoku Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'two_factor_enabled' => false,
            ]
        );
    }
}
