<?php

namespace Database\Seeders;

// Import Model

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
        // p2mElektronik::factory(50)->create();
        // p2mOnline::factory(50)->create();

        $this->call([
            P2mSosialisasiSeeder::class,
            P2mUpacaraSeeder::class,
            P2mKieSeeder::class,
            P2mLingkunganBersinarSeeder::class,
            P2mCfdSeeder::class,
            P2mElektronikSeeder::class,
            P2mNonElektronikSeeder::class,
            P2mOnlineSeeder::class,
            P2mTesUrineSeeder::class,
            P2mDesaBersinarSeeder::class,
            P2mSafariReligiSeeder::class
        ]);


        // Buat User Login
        $this->call([
            UserSeeder::class
        ]);
    }
}