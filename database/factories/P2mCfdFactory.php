<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\p2mcfd;
use App\Models\SatuanKerja;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\P2mSosialisasi>
 */
class P2mCfdFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = p2mcfd::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Ambil ID Satuan Kerja yang sudah ada secara acak
            'satuan_kerja_id' => SatuanKerja::inRandomOrder()->first()->id ?? SatuanKerja::factory(),          
            'tempat_kegiatan' => $this->faker->address(),
            'tanggal_pelaksanaan' => $this->faker->dateTimeBetween('-1 year', 'now'), // Tanggal setahun terakhir
            'jumlah_peserta' => $this->faker->numberBetween(10, 500),
            'link_kelengkapan_dokumentasi' => $this->faker->url(),
        ];
    }
}