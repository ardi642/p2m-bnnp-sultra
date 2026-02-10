<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\P2mInformasiEdukasi;
use App\Models\Pegawai;

class P2mInformasiEdukasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Membuat 50 data dummy Sosialisasi
        P2mInformasiEdukasi::factory(150)->create()->each(function ($kegiatan) {
            
            // Ambil pegawai dari satker yang sama dengan kegiatan
            // Ambil acak 2 s/d 4 orang
            $listPegawai = Pegawai::where('satuan_kerja_id', $kegiatan->satuan_kerja_id)
                ->inRandomOrder()
                ->take(rand(2, 4))
                ->get();

            $attachData = [];
            foreach ($listPegawai as $pgw) {
                // Mengisi data pivot (history satker)
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