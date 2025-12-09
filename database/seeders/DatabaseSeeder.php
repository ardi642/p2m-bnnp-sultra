<?php

namespace Database\Seeders;

use App\Models\P2mSosialisasi;
use App\Models\p2mcfd;
use App\Models\P2mDesaBersinar;
use App\Models\p2mElektronik;
use App\Models\p2mOnline;
use App\Models\P2mSafariReligi;
use App\Models\P2mTesUrine; // <--- Import Model Baru
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
        
        // Buat Pegawai
        Pegawai::factory(200)->create(); 

        p2mElektronik::factory(50)->create();
        p2mOnline::factory(50)->create();

        // 2. Seeding P2M Sosialisasi
        P2mSosialisasi::factory(50)->create()->each(function ($kegiatan) {
            $pegawaiSesuaiSatker = Pegawai::where('satuan_kerja_id', $kegiatan->satuan_kerja_id)
                ->inRandomOrder()->take(rand(2, 4))->pluck('nip');
            
            if ($pegawaiSesuaiSatker->isNotEmpty()) {
                $kegiatan->pegawai()->attach($pegawaiSesuaiSatker);
            }
        });

        // 3. Seeding P2M CFD
        p2mcfd::factory(50)->create()->each(function ($kegiatan) {
            $pegawaiSesuaiSatker = Pegawai::where('satuan_kerja_id', $kegiatan->satuan_kerja_id)
                ->inRandomOrder()->take(rand(2, 4))->pluck('nip');
            
            if ($pegawaiSesuaiSatker->isNotEmpty()) {
                $kegiatan->pegawai()->attach($pegawaiSesuaiSatker);
            }
        });

        // ---------------------------------------------------------
        // 4. Seeding P2M Tes Urine (BARU)
        // ---------------------------------------------------------
        P2mTesUrine::factory(50) // Buat 50 data dummy
        ->create()
        ->each(function ($kegiatan) {
            
            // Ambil pegawai (Tim/Katim) yang SATKER-nya SAMA
            $pegawaiSesuaiSatker = Pegawai::where('satuan_kerja_id', $kegiatan->satuan_kerja_id)
                ->inRandomOrder()
                ->take(rand(3, 6)) // Tim tes urine biasanya butuh lebih banyak orang (3-6)
                ->pluck('nip');
            
            // Attach ke pivot
            if ($pegawaiSesuaiSatker->isNotEmpty()) {
                $kegiatan->pegawai()->attach($pegawaiSesuaiSatker);
            }
        });

        P2mDesaBersinar::factory(50)
        ->create()
        ->each(fn ($d) => $d->pegawai()->attach(
            Pegawai::where('satuan_kerja_id', $d->satuan_kerja_id)
                ->inRandomOrder()
                ->take(rand(1, 3))
                ->pluck('nip')
        ));

        P2mSafariReligi::factory(50) // Buat 50 data dummy
        ->create()
        ->each(function ($kegiatan) {
            
            // Ambil pegawai yang SATKER-nya SAMA dengan kegiatan
            // Safari religi biasanya melibatkan tim kecil (2-5 orang)
            $pegawaiSesuaiSatker = Pegawai::where('satuan_kerja_id', $kegiatan->satuan_kerja_id)
                ->inRandomOrder()
                ->take(rand(2, 5)) 
                ->pluck('nip');
            
            // Attach ke pivot table
            if ($pegawaiSesuaiSatker->isNotEmpty()) {
                $kegiatan->pegawai()->attach($pegawaiSesuaiSatker);
            }
        });

        $this->call([
            UserSeeder::class
        ]);
    }
}