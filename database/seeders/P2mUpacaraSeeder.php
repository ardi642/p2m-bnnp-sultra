<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\P2mUpacara;
use App\Models\Pegawai;

class P2mUpacaraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Membuat 50 data dummy Upacara
        P2mUpacara::factory(150)->create()->each(function ($kegiatan) {
            
            // Ambil pegawai dari satker yang sama dengan kegiatan
            // (Random 2 sampai 4 orang)
            $listPegawai = Pegawai::where('satuan_kerja_id', $kegiatan->satuan_kerja_id)
                ->inRandomOrder()
                ->take(rand(2, 4))
                ->get();

            $attachData = [];
            foreach ($listPegawai as $pgw) {
                // Format array untuk attach dengan kolom tambahan (pivot)
                // saved_satuan_kerja_id diisi agar data tidak dianggap "pindahan" saat baru dibuat
                $attachData[$pgw->nip] = [
                    'saved_satuan_kerja_id' => $pgw->satuan_kerja_id 
                ];
            }

            // Simpan ke tabel pivot pegawai_p2m_upacara
            if (!empty($attachData)) {
                $kegiatan->pegawai()->attach($attachData);
            }
        });
    }
}