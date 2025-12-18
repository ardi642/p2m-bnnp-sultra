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

        $this->call([
            P2mUpacaraSeeder::class,
            P2mKieSeeder::class,
            P2mLingkunganBersinarSeeder::class,
            P2mCfdSeeder::class,
            P2mElektronikSeeder::class
        ]);

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