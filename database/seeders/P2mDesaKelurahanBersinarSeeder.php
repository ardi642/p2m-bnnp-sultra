<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\P2mDesaKelurahanBersinar;
use App\Models\Pegawai;

class P2mDesaKelurahanBersinarSeeder extends Seeder
{
    public function run(): void
    {
        P2mDesaKelurahanBersinar::factory(50)->create()->each(function ($kegiatan) {
            $listPegawai = Pegawai::where('satuan_kerja_id', $kegiatan->satuan_kerja_id)
                ->inRandomOrder()
                ->take(rand(1, 3))
                ->get();

            $attachData = [];
            foreach ($listPegawai as $pgw) {
                $attachData[$pgw->nip] = [
                    'saved_satuan_kerja_id' => $pgw->satuan_kerja_id 
                ];
            }

            if (!empty($attachData)) {
                $kegiatan->pegawai()->attach($attachData);
            }
        });
    }
}