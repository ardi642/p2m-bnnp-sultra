<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\P2mTesUrine;
use App\Models\Pegawai;

class P2mTesUrineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Membuat 50 data dummy Tes Urine
        P2mTesUrine::factory(50)->create()->each(function ($kegiatan) {
            
            // LOGIKA PIVOT (PANITIA)
            // 1. Ambil pegawai yang satu satker dengan kegiatan ini
            $listPegawai = Pegawai::where('satuan_kerja_id', $kegiatan->satuan_kerja_id)
                ->inRandomOrder()
                ->take(rand(3, 6)) // Ambil 3 s/d 6 pegawai sebagai panitia
                ->get();

            $attachData = [];
            foreach ($listPegawai as $pgw) {
                // Simpan history satker pegawai saat ini ke tabel pivot
                $attachData[$pgw->nip] = [
                    'saved_satuan_kerja_id' => $pgw->satuan_kerja_id 
                ];
            }

            // 2. Attach ke tabel pivot 'pegawai_p2m_tes_urine'
            if (!empty($attachData)) {
                $kegiatan->pegawai()->attach($attachData);
            }
        });
    }
}