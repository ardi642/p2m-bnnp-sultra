<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\P2mKie;
use App\Models\Pegawai;

class P2mKieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Membuat 50 data dummy KIE Keliling
        P2mKie::factory(50)->create()->each(function ($kegiatan) {
            
            // Ambil pegawai dari satker yang sama dengan kegiatan
            // Ambil acak 2 s/d 5 orang
            $listPegawai = Pegawai::where('satuan_kerja_id', $kegiatan->satuan_kerja_id)
                ->inRandomOrder()
                ->take(rand(2, 5))
                ->get();

            $attachData = [];
            foreach ($listPegawai as $pgw) {
                // Mengisi data pivot (history satker)
                // PENTING: Agar di tabel tidak muncul error / merah (pindah satker)
                $attachData[$pgw->nip] = [
                    'saved_satuan_kerja_id' => $pgw->satuan_kerja_id 
                ];
            }

            // Simpan relasi ke tabel pivot
            if (!empty($attachData)) {
                $kegiatan->pegawai()->attach($attachData);
            }
        });
    }
}