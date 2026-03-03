<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RehabTarget;
use App\Models\SatuanKerja;

class RehabTargetSeeder extends Seeder
{
    public function run(): void
    {
        $satkerIds = SatuanKerja::pluck('id');
        $tahuns = [2024, 2025, 2026];

        foreach ($satkerIds as $satkerId) {
            foreach ($tahuns as $tahun) {
                // updateOrCreate agar tidak error unique constraint jika dijalankan ulang
                RehabTarget::updateOrCreate(
                    ['satuan_kerja_id' => $satkerId, 'tahun' => $tahun],
                    [
                        'target_rawat_jalan' => rand(50, 150),
                        'target_pasca_rehab' => rand(30, 80),
                        'target_skhpn' => rand(100, 300),
                    ]
                );
            }
        }
    }
}