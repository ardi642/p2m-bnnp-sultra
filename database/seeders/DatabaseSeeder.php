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

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([
            SatuanKerjaSeeder::class, 
        ]);
        Pegawai::factory(50)->create();

        p2mElektronik::factory((100))->create();
        p2mOnline::factory((100))->create();

        P2mSosialisasi::factory(100)
        ->create()
        ->each(function ($kegiatan) {
            // Ambil 3 sampai 5 pegawai acak
            $pegawaiAcak = Pegawai::inRandomOrder()->take(rand(3, 5))->pluck('nip');
            
            // Hubungkan (Attach) ke kegiatan
            $kegiatan->pegawai()->attach($pegawaiAcak);
        });
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);


        p2mcfd::factory(100)
        ->create()
        ->each(function ($kegiatan) {
            // Ambil 3 sampai 5 pegawai acak
            $pegawaiAcak = Pegawai::inRandomOrder()->take(rand(3, 5))->pluck('nip');
            
            // Hubungkan (Attach) ke kegiatan
            $kegiatan->pegawai()->attach($pegawaiAcak);
        });

        $this->call([
            UserSeeder::class
        ]);
    }
}
