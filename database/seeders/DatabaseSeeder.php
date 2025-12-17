<?php

namespace Database\Seeders;

// Import Model
use App\Models\P2mSosialisasi;
use App\Models\p2mcfd;
use App\Models\P2mDesaBersinar;
use App\Models\p2mElektronik;
use App\Models\P2mMediaNonElektronik;
use App\Models\p2mOnline;
use App\Models\P2mSafariReligi;
use App\Models\P2mTesUrine;
use App\Models\Pegawai;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Data Master
        $this->call([
            SatuanKerjaSeeder::class,
            KabupatenKotaSeeder::class
        ]);
        
        // 2. Buat Data Pegawai
        Pegawai::factory(200)->create(); 

        // 3. Buat Data Dummy Lain
        p2mElektronik::factory(50)->create();
        p2mOnline::factory(50)->create();
        P2mMediaNonElektronik::factory(50)->create();

        // =========================================================
        // 4. SEEDING P2M SOSIALISASI (YANG DIPERBAIKI)
        // =========================================================
        // Hanya di sini kita isi 'saved_satuan_kerja_id' agar tampilan
        // di tabel P2M Sosialisasi bersih (tidak merah/pindah).
        // =========================================================
        P2mSosialisasi::factory(50)->create()->each(function ($kegiatan) {
            // Ambil pegawai dari satker yang sama
            $listPegawai = Pegawai::where('satuan_kerja_id', $kegiatan->satuan_kerja_id)
                ->inRandomOrder()
                ->take(rand(2, 4))
                ->get();

            $attachData = [];
            foreach ($listPegawai as $pgw) {
                $attachData[$pgw->nip] = [
                    // KITA ISI INI KHUSUS UNTUK SOSIALISASI
                    'saved_satuan_kerja_id' => $pgw->satuan_kerja_id 
                ];
            }

            if (!empty($attachData)) {
                $kegiatan->pegawai()->attach($attachData);
            }
        });

        // =========================================================
        // 5. SEEDING MODEL LAIN (STANDAR / DEFAULT)
        // =========================================================
        // Bagian ini dikembalikan ke standar (attach NIP saja).
        // Tidak mengisi kolom history agar tidak error "Column not found".
        // =========================================================

        // P2M CFD
        p2mcfd::factory(50)->create()->each(function ($kegiatan) {
            $pegawaiNips = Pegawai::where('satuan_kerja_id', $kegiatan->satuan_kerja_id)
                ->inRandomOrder()->take(rand(2, 4))->pluck('nip');
            
            if ($pegawaiNips->isNotEmpty()) {
                $kegiatan->pegawai()->attach($pegawaiNips);
            }
        });

        // P2M Tes Urine
        P2mTesUrine::factory(50)->create()->each(function ($kegiatan) {
            $pegawaiNips = Pegawai::where('satuan_kerja_id', $kegiatan->satuan_kerja_id)
                ->inRandomOrder()->take(rand(3, 6))->pluck('nip');
            
            if ($pegawaiNips->isNotEmpty()) {
                $kegiatan->pegawai()->attach($pegawaiNips);
            }
        });

        // P2M Desa Bersinar
        P2mDesaBersinar::factory(50)->create()->each(function ($kegiatan) {
            $pegawaiNips = Pegawai::where('satuan_kerja_id', $kegiatan->satuan_kerja_id)
                ->inRandomOrder()->take(rand(1, 3))->pluck('nip');
            
            if ($pegawaiNips->isNotEmpty()) {
                $kegiatan->pegawai()->attach($pegawaiNips);
            }
        });

        // P2M Safari Religi
        P2mSafariReligi::factory(50)->create()->each(function ($kegiatan) {
            $pegawaiNips = Pegawai::where('satuan_kerja_id', $kegiatan->satuan_kerja_id)
                ->inRandomOrder()->take(rand(2, 5))->pluck('nip');
            
            if ($pegawaiNips->isNotEmpty()) {
                $kegiatan->pegawai()->attach($pegawaiNips);
            }
        });

        // Buat User Login
        $this->call([
            UserSeeder::class
        ]);
    }
}