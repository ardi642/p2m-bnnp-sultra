<?php

namespace Database\Seeders;

use App\Models\P2mSosialisasi;
use App\Models\p2mcfd;
use App\Models\p2mElektronik;
use App\Models\p2mOnline;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Pegawai;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Buat Data Master
        $this->call([
            SatuanKerjaSeeder::class, 
        ]);
        
        // Saran: Tambah jumlah pegawai agar tiap satker kebagian orang
        Pegawai::factory(200)->create(); 

        p2mElektronik::factory(50)->create();
        p2mOnline::factory(50)->create();

        // 2. Seeding P2M Sosialisasi (DIPERBAIKI)
        P2mSosialisasi::factory(100)
        ->create()
        ->each(function ($kegiatan) {
            
            // --- LOGIKA PERBAIKAN ---
            // Ambil pegawai yang SATUAN KERJA-nya SAMA dengan KEGIATAN ini
            $pegawaiSesuaiSatker = Pegawai::where('satuan_kerja_id', $kegiatan->satuan_kerja_id)
                ->inRandomOrder()
                ->take(rand(3, 5))
                ->pluck('nip');
            
            // Hanya attach jika ada pegawainya
            if ($pegawaiSesuaiSatker->isNotEmpty()) {
                $kegiatan->pegawai()->attach($pegawaiSesuaiSatker);
            }
        });

        // 3. Seeding P2M CFD (DIPERBAIKI)
        p2mcfd::factory(100)
        ->create()
        ->each(function ($kegiatan) {
            
            // --- LOGIKA PERBAIKAN (Sama) ---
            $pegawaiSesuaiSatker = Pegawai::where('satuan_kerja_id', $kegiatan->satuan_kerja_id)
                ->inRandomOrder()
                ->take(rand(3, 5))
                ->pluck('nip');
            
            if ($pegawaiSesuaiSatker->isNotEmpty()) {
                $kegiatan->pegawai()->attach($pegawaiSesuaiSatker);
            }
        });

        $this->call([
            UserSeeder::class
        ]);
    }
}