<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RehabPasien;
use App\Models\RehabRiwayat;
use App\Models\BerantasNarkotika;

class RehabPasienSeeder extends Seeder
{
    public function run(): void
    {
        $narkotikaIds = BerantasNarkotika::pluck('id')->toArray();

        if (empty($narkotikaIds)) {
            $this->command->error('Tabel narkotika masih kosong! Harap isi master narkotika dulu.');
            return;
        }

        // Bikin 100 Pasien menggunakan Factory
        RehabPasien::factory(100)->create()->each(function ($pasien) use ($narkotikaIds) {
            
            // Untuk tiap Pasien, bikin 1 Riwayat
            $riwayat = RehabRiwayat::factory()->create([
                'rehab_pasien_id' => $pasien->id
            ]);

            // Tempelkan Narkotika secara acak (1 sampai 3 jenis narkotika per riwayat)
            $jumlahNarkotika = rand(1, 3);
            $narkotikaTerpilih = fake()->randomElements($narkotikaIds, $jumlahNarkotika);
            
            $riwayat->narkotika()->attach($narkotikaTerpilih);
        });
    }
}