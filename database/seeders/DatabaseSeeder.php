<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create initial admin user
        User::create([
            'name' => 'Khonsaa Hilmi',
            'email' => 'khonsaa.mufiida@mhs.unsoed.ac.id',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Create sample employee
        User::create([
            'name' => 'Andi Permana',
            'email' => 'contoh.email@pln.co.id',
            'password' => Hash::make('employee123'),
            'role' => 'employee',
            'status' => 'deactive',
        ]);
    }
}
