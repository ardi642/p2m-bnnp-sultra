<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\P2mDesaKelurahanBersinar;
use App\Models\SatuanKerja;
use App\Models\KabupatenKota;

class P2mDesaKelurahanBersinarFactory extends Factory
{
    protected $model = P2mDesaKelurahanBersinar::class;

    public function definition(): array
    {
        return [
            'satuan_kerja_id' => SatuanKerja::inRandomOrder()->first()->id ?? SatuanKerja::factory(),
            'kabupaten_kota_id' => KabupatenKota::inRandomOrder()->first()->id ?? 1,
            'anggaran_pembentukan' => $this->faker->randomElement(['DIPA', 'NON DIPA']),
            'nama_desa_kelurahan' => $this->faker->citySuffix(),
            'tanggal_pencanangan' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'jumlah_penggiat' => $this->faker->numberBetween(5, 50),
            'keberadaan_ibm' => $this->faker->randomElement(['Ada', 'Belum Ada']),
            'no_hp_penanggung_jawab' => $this->faker->phoneNumber(),
        ];
    }
}