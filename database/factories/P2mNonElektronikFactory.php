<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\P2mNonElektronik;
use App\Models\SatuanKerja;

class P2mNonElektronikFactory extends Factory
{
    protected $model = P2mNonElektronik::class;

    public function definition(): array
    {
        return [
            'satuan_kerja_id' => SatuanKerja::inRandomOrder()->first()->id ?? SatuanKerja::factory(),
            'anggaran_pelaksanaan' => $this->faker->randomElement(['DIPA', 'NON DIPA']),
            'jenis_media' => $this->faker->randomElement([
                'Media Cetak', 'Media Luar Ruang', 'Branding Sarana Publik'
            ]),
            'tempat_pemasangan' => $this->faker->address(),
            'tanggal_mulai_pelaksanaan' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'durasi_pelaksanaan' => $this->faker->numberBetween(1, 30),
        ];
    }
}