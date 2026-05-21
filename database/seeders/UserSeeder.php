<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = Hash::make('password'); // Password Default

        // ---------------------------------------------------
        // 1. BUAT SUPER ADMIN (Hanya 1 Akun Global)
        // ---------------------------------------------------
        $superAdminEmail = 'bnnpsultra5@gmail.com';
        User::create([
            'name' => 'Super Administrator',
            'email' => $superAdminEmail,
            'password' => $defaultPassword,
            'role' => 'admin',
            'pegawai_nip' => null, // Tidak terikat satker/pegawai
            'is_password_default' => true,
            
            // --- TAMBAHAN PENTING ---
            'email_verified_at' => now(), // Langsung verified agar bisa login
            'pending_email' => null,      // Pastikan bersih
            'verification_token' => null, // Pastikan bersih
        ]);
        $this->command->info('Super Admin berhasil dibuat.');
        
    }
}