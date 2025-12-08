<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\P2mDesaBersinar;
use App\Models\SatuanKerja;
use App\Models\KabupatenKota;

class P2mDesaBersinarFactory extends Factory
{
    protected $model = P2mDesaBersinar::class;

    public function definition(): array
    {
        return [
            'satuan_kerja_id' => SatuanKerja::inRandomOrder()->firstOrFail()->id,
            'anggaran_pembentukan' => $this->faker->randomElement(['DIPA', 'NON DIPA']),
            'nama_desa' => $this->faker->word() . ' ' . $this->faker->citySuffix(),
            'nama_kelurahan' => 'Kelurahan ' . $this->faker->word(),
            'kabupaten_kota_id' => KabupatenKota::inRandomOrder()->firstOrFail()->id,
            'tanggal_pencanangan' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'jumlah_penggiat' => $this->faker->numberBetween(5, 50),
            'keberadaan_ibm' => $this->faker->randomElement(['ada', 'belum ada']),
            'nomor_hp_penanggung_jawab' => $this->faker->phoneNumber(),
            'link_kelengkapan_dokumentasi' => $this->faker->url(),
        ];
    }
}