<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KabupatenKota;

class KabupatenKotaSeeder extends Seeder
{
    public function run(): void
    {
        $list = [
            'Kota Kendari',
            'Konawe',
            'Konawe Selatan',
            'Konawe Utara',
            'Konawe Tengah',
            'Konawe Kepulauan',
            'Kolaka',
            'Kolaka Utara',
            'Kolaka Timur',
            'Muna',
            'Muna Barat',
            'Buton',
            'Buton Utara',
            'Buton Tengah',
            'Buton Selatan',
            'Wakatobi',
            'Bombana',
            'Baubau',
        ];

        foreach ($list as $nama) {
            KabupatenKota::firstOrCreate(['nama' => $nama]);
        }
    }
}