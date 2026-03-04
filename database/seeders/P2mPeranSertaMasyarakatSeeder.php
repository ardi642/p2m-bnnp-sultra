<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\P2mPeranSertaMasyarakat;
use App\Models\Pegawai;

class P2mPeranSertaMasyarakatSeeder extends Seeder
{
    public function run(): void
    {
        // Membuat 150 data dummy Peran Serta Masyarakat
        P2mPeranSertaMasyarakat::factory(150)->create()->each(function ($kegiatan) {
            
            // Ambil pegawai dari satker yang sama dengan kegiatan (acak 2 - 4 orang)
            $listPegawai = Pegawai::where('satuan_kerja_id', $kegiatan->satuan_kerja_id)
                ->inRandomOrder()
                ->take(rand(2, 4))
                ->get();

            $attachData = [];
            foreach ($listPegawai as $pgw) {
                // Mengisi data pivot (history satker saat diinput)
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