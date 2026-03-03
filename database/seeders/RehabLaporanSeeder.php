<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RehabLaporan;
use App\Models\SatuanKerja;
use Carbon\Carbon;
use Faker\Factory as Faker;

class RehabLaporanSeeder extends Seeder
{
    public function run(): void
    {
        $satkerIds = SatuanKerja::pluck('id');
        $faker = Faker::create();

        foreach ($satkerIds as $satkerId) {
            // Membuat 50 laporan acak per Satker antara 2024 sampai 2026
            for ($i = 0; $i < 50; $i++) {
                $tanggal = $faker->dateTimeBetween('2024-01-01', 'now')->format('Y-m-d');
                
                RehabLaporan::updateOrCreate(
                    ['satuan_kerja_id' => $satkerId, 'tanggal' => $tanggal],
                    [
                        'realisasi_rawat_jalan' => rand(0, 5),
                        'realisasi_pasca_rehab' => rand(0, 3),
                        'realisasi_skhpn' => rand(1, 10),
                    ]
                );
            }
        }
    }
}