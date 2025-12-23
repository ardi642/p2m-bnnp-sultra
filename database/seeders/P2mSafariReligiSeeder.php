<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\P2mSafariReligi;
use App\Models\Pegawai;

class P2mSafariReligiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat 50 data dummy Safari Religi
        P2mSafariReligi::factory(50)->create()->each(function ($kegiatan) {
            
            // 1. Ambil pegawai yang SATKER-nya SAMA dengan lokasi kegiatan
            // (Simulasi logis: biasanya kegiatan dilakukan oleh pegawai satker setempat)
            $listPegawai = Pegawai::where('satuan_kerja_id', $kegiatan->satuan_kerja_id)
                ->inRandomOrder()
                ->take(rand(2, 5)) // Ambil acak 2 sampai 5 orang
                ->get();

            // Jika satker tersebut tidak punya pegawai, coba ambil dari satker lain (fallback)
            if ($listPegawai->isEmpty()) {
                $listPegawai = Pegawai::inRandomOrder()->take(2)->get();
            }

            // 2. Siapkan data untuk tabel pivot (termasuk history satker)
            $attachData = [];
            foreach ($listPegawai as $pgw) {
                $attachData[$pgw->nip] = [
                    'saved_satuan_kerja_id' => $pgw->satuan_kerja_id 
                ];
            }

            // 3. Simpan relasi
            if (!empty($attachData)) {
                $kegiatan->pegawai()->attach($attachData);
            }
        });
    }
}