<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SatuanKerja;

class SatuanKerjaSeeder extends Seeder
{
    public function run(): void
    {
        $satkerList = [
            'BNNP Sultra',
            'BNNK Kendari',
            'BNNK Kolaka',
            'BNNK Muna',
            'BNNK Bau-bau',
            'BNNK Konawe',
        ];

        foreach ($satkerList as $nama) {
            SatuanKerja::firstOrCreate(
                ['satuan_kerja' => $nama], // kondisi pencarian (harus unique)
                ['satuan_kerja' => $nama]  // data untuk dibuat jika belum ada
            );
        }
    }
}