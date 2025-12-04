<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Pegawai;
use App\Models\SatuanKerja;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $passwordDefault = Hash::make('12345678');

        // ==========================================
        // 1. BUAT SUPER ADMIN (PUSAT)
        // ==========================================
        // Akun ini tidak terikat ke pegawai manapun (pegawai_nip = null)
        User::firstOrCreate(
            ['email' => 'superadmin@bnn.go.id'], // Cek email biar gak duplikat
            [
                'name'              => 'Super Administrator',
                'password'          => $passwordDefault,
                'pegawai_nip'       => null, 
                'email_verified_at' => now(),
            ]
        );
        
        $this->command->info('✅ Super Admin Created.');

        // ==========================================
        // 2. BUAT USER DARI PEGAWAI PER SATKER
        // ==========================================
        
        // Ambil semua satker yang ada
        $satkers = SatuanKerja::all();

        foreach ($satkers as $satker) {
            // Ambil 1 pegawai saja dari satker ini sebagai perwakilan admin
            $pegawai = Pegawai::where('satuan_kerja_id', $satker->id)->first();

            // Cek: Apakah satker ini punya pegawai?
            if ($pegawai) {
                
                // Cek: Apakah pegawai ini sudah punya user sebelumnya?
                $userExist = User::where('pegawai_nip', $pegawai->nip)->exists();

                if (!$userExist) {
                    User::create([
                        'name'              => $pegawai->nama, // Pakai nama asli pegawai
                        'email'             => $pegawai->email, // Pakai email asli pegawai
                        'password'          => $passwordDefault,
                        'pegawai_nip'       => $pegawai->nip, // Link ke data pegawai
                        'email_verified_at' => now(),
                    ]);

                    $this->command->info("👤 User dibuat untuk Satker: {$satker->nama} (Admin: {$pegawai->nama})");
                }
            } else {
                $this->command->warn("⚠️ Satker {$satker->nama} tidak memiliki data pegawai, user dilewati.");
            }
        }
    }
}