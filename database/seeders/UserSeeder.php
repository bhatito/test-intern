<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Manager PPIC
        User::create([
            'id' => Str::uuid(),
            'name' => 'Manager PPIC',
            'email' => 'manager.ppic@example.com',
            'password' => Hash::make('password123'),
            'role' => 'managerppic',
            'status' => 'active',
            'department' => 'ppic',
        ]);

        // Staff PPIC
        User::create([
            'id' => Str::uuid(),
            'name' => 'Staff PPIC',
            'email' => 'staff.ppic@example.com',
            'password' => Hash::make('password123'),
            'role' => 'staffppic',
            'status' => 'active',
            'department' => 'ppic',
        ]);

        // Manager Produksi
        User::create([
            'id' => Str::uuid(),
            'name' => 'Manager Produksi',
            'email' => 'manager.produksi@example.com',
            'password' => Hash::make('password123'),
            'role' => 'managerpproduksi',
            'status' => 'active',
            'department' => 'produksi',
        ]);

        // Staff Produksi
        User::create([
            'id' => Str::uuid(),
            'name' => 'Staff Produksi',
            'email' => 'staff.produksi@example.com',
            'password' => Hash::make('password123'),
            'role' => 'staffproduksi',
            'status' => 'active',
            'department' => 'produksi',
        ]);
    }
}
