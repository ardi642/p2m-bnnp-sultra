<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\SatuanKerja;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = Hash::make('password'); // Password Default

        // ---------------------------------------------------
        // 1. BUAT SUPER ADMIN (Hanya 1 Akun Global)
        // ---------------------------------------------------
        $adminEmail = 'admin@bnn.go.id';
        
        // Cek dulu apakah admin sudah ada
        if (!User::where('email', $adminEmail)->exists()) {
            User::create([
                'name' => 'Super Administrator',
                'email' => $adminEmail,
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

        // ---------------------------------------------------
        // 2. BUAT USER ADMIN SATKER & OPERATOR (Per Satker)
        // ---------------------------------------------------
        // Ambil semua satker yang punya pegawai
        $satuanKerjas = SatuanKerja::with('pegawai')->get();

        foreach ($satuanKerjas as $satker) {
            $pegawais = $satker->pegawai;

            // Pastikan ada pegawai di satker ini
            if ($pegawais->isEmpty()) {
                continue; 
            }

            // --- A. BUAT ADMIN SATKER (Ambil Pegawai Pertama) ---
            if ($pegawais->count() >= 1) {
                $calonAdmin = $pegawais[0];
                
                // Cek apakah pegawai ini sudah punya user?
                $cekUser = User::where('pegawai_nip', $calonAdmin->nip)->first();

                if (!$cekUser) {
                    // Buat format email unik: admin.namasatker@bnn.go.id
                    $emailSatker = 'admin.' . strtolower(str_replace([' ', '.'], '', $satker->satuan_kerja)) . '@bnn.go.id';

                    User::create([
                        'name' => $calonAdmin->nama,
                        'email' => $emailSatker,
                        'password' => $defaultPassword,
                        'role' => 'admin_satker',
                        'pegawai_nip' => $calonAdmin->nip,
                        'is_password_default' => true,

                        // --- TAMBAHAN PENTING ---
                        'email_verified_at' => now(),
                        'pending_email' => null,
                        'verification_token' => null,
                    ]);
                }
            }

            // --- B. BUAT OPERATOR (Ambil Pegawai Kedua) ---
            if ($pegawais->count() >= 2) {
                $calonOperator = $pegawais[1];
                
                // Cek apakah pegawai ini sudah punya user?
                $cekUser = User::where('pegawai_nip', $calonOperator->nip)->first();

                if (!$cekUser) {
                    $emailOperator = 'operator.' . strtolower(str_replace([' ', '.'], '', $satker->satuan_kerja)) . '@bnn.go.id';

                    User::create([
                        'name' => $calonOperator->nama,
                        'email' => $emailOperator,
                        'password' => $defaultPassword,
                        'role' => 'operator_satker',
                        'pegawai_nip' => $calonOperator->nip,
                        'is_password_default' => true,

                        // --- TAMBAHAN PENTING ---
                        'email_verified_at' => now(),
                        'pending_email' => null,
                        'verification_token' => null,
                    ]);
                }
            }
        }
    }
}