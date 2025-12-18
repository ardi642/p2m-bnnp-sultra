<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\P2mLingkunganBersinar;
use App\Models\Pegawai;

class P2mLingkunganBersinarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Buat 50 data dummy menggunakan Factory
        P2mLingkunganBersinar::factory(50)->create()->each(function ($kegiatan) {
            
            // 2. Logika mengisi Tabel Pivot (Pegawai yang ditugaskan)
            // Kita ambil 1 s/d 3 pegawai secara acak dari Satuan Kerja yang SAMA dengan kegiatannya
            $listPegawai = Pegawai::where('satuan_kerja_id', $kegiatan->satuan_kerja_id)
                ->inRandomOrder()
                ->take(rand(1, 3)) // Ambil 1, 2, atau 3 orang
                ->get();

            $attachData = [];

            foreach ($listPegawai as $pgw) {
                // Format array untuk sync/attach
                // Key = NIP, Value = Array kolom tambahan di pivot
                $attachData[$pgw->nip] = [
                    'saved_satuan_kerja_id' => $pgw->satuan_kerja_id 
                ];
            }

            // 3. Simpan ke tabel pivot 'pegawai_p2m_lingkungan_bersinar'
            if (!empty($attachData)) {
                $kegiatan->pegawai()->attach($attachData);
            }
        });
    }
}