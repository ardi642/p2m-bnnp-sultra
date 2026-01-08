<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BerantasNarkotika;

class BerantasNarkotikaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            // --- GOLONGAN I (Hanya untuk ilmu pengetahuan, potensi ketergantungan sangat tinggi) ---
            ['nama_narkotika' => 'Sabu (Methamphetamine)', 'golongan' => 'Golongan I'],
            ['nama_narkotika' => 'Ganja (Cannabis Sativa)', 'golongan' => 'Golongan I'],
            ['nama_narkotika' => 'Ekstasi (MDMA)', 'golongan' => 'Golongan I'],
            ['nama_narkotika' => 'Heroin (Putaw)', 'golongan' => 'Golongan I'],
            ['nama_narkotika' => 'Kokain', 'golongan' => 'Golongan I'],
            ['nama_narkotika' => 'Opium', 'golongan' => 'Golongan I'],
            ['nama_narkotika' => 'Tembakau Gorila (Sintetis)', 'golongan' => 'Golongan I'],
            ['nama_narkotika' => 'LSD (Lysergic Acid Diethylamide)', 'golongan' => 'Golongan I'],
            ['nama_narkotika' => 'Khat (Katinona)', 'golongan' => 'Golongan I'],

            // --- GOLONGAN II (Pengobatan pilihan terakhir, potensi tinggi) ---
            ['nama_narkotika' => 'Morfin', 'golongan' => 'Golongan II'],
            ['nama_narkotika' => 'Petidin', 'golongan' => 'Golongan II'],
            ['nama_narkotika' => 'Fentanil', 'golongan' => 'Golongan II'],
            ['nama_narkotika' => 'Metadon', 'golongan' => 'Golongan II'],

            // --- GOLONGAN III (Banyak untuk terapi, potensi ringan) ---
            ['nama_narkotika' => 'Kodein', 'golongan' => 'Golongan III'],
            ['nama_narkotika' => 'Buprenorfin', 'golongan' => 'Golongan III'],
            ['nama_narkotika' => 'Etilmorfina', 'golongan' => 'Golongan III'],

            // --- NON GOLONGAN (Obat Keras / Psikotropika / Prekursor yang sering disalahgunakan) ---
            ['nama_narkotika' => 'Tramadol', 'golongan' => 'Non Golongan'],
            ['nama_narkotika' => 'Trihexyphenidyl (Pil Sapi/Yarindo)', 'golongan' => 'Non Golongan'],
            ['nama_narkotika' => 'Dextromethorphan', 'golongan' => 'Non Golongan'],
            ['nama_narkotika' => 'Alprazolam', 'golongan' => 'Non Golongan'], 
            ['nama_narkotika' => 'Dumolid', 'golongan' => 'Non Golongan'],
            ['nama_narkotika' => 'Riklona', 'golongan' => 'Non Golongan'],
            ['nama_narkotika' => 'Komix (Penyalahgunaan)', 'golongan' => 'Non Golongan'],
        ];

        foreach ($data as $item) {
            BerantasNarkotika::firstOrCreate(
                ['nama_narkotika' => $item['nama_narkotika']], // Cek duplikat berdasarkan nama
                ['golongan' => $item['golongan']]
            );
        }
    }
}