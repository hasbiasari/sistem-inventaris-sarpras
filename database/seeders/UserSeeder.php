<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Mahasiswa;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin TU
        User::create([
            'name' => 'Admin TU',
            'email' => 'admin@sttcipasung.ac.id',
            'password' => Hash::make('admin123'),
            'role' => 'admin_tu',
        ]);

        // 2. Pimpinan
        User::create([
            'name' => 'Pimpinan',
            'email' => 'pimpinan@sttcipasung.ac.id',
            'password' => Hash::make('pimpinan123'),
            'role' => 'pimpinan',
        ]);

        // 3. Mahasiswa - Muhammad Hasbi As'ari
        $mahasiswaUser = User::create([
            'name' => "Muhammad Hasbi As'ari",
            'email' => 'hasbi10222175@sttcipasung.ac.id',
            'password' => Hash::make('10222175'),
            'role' => 'mahasiswa',
        ]);

        Mahasiswa::create([
            'user_id' => $mahasiswaUser->id,
            'nim' => '10222175',
            'nama' => "Muhammad Hasbi As'ari",
            'status' => 'Aktif',
        ]);
    }
}