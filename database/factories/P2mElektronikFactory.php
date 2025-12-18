<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\P2mElektronik;
use App\Models\SatuanKerja;

class P2mElektronikFactory extends Factory
{
    protected $model = P2mElektronik::class;

    public function definition(): array
    {
        return [
            'satuan_kerja_id' => SatuanKerja::inRandomOrder()->first()->id ?? SatuanKerja::factory(),
            'anggaran_pelaksanaan' => $this->faker->randomElement(['DIPA', 'NON DIPA']),
            'jenis_media' => $this->faker->randomElement(['televisi', 'radio', 'video tron', 'bioskop', 'tv plasma', 'media lain']),
            'nama_media' => $this->faker->company() . ' Channel',
            'tanggal_pelaksanaan' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'durasi_pelaksanaan' => $this->faker->numberBetween(1, 30),
        ];
    }
}