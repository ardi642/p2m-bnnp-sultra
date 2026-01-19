<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\P2mCfd;
use App\Models\SatuanKerja;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\P2mCfd>
 */
class P2mCfdFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = P2mCfd::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Ambil ID Satker acak yang ada, atau buat baru jika kosong
            'satuan_kerja_id' => SatuanKerja::inRandomOrder()->first()->id ?? SatuanKerja::factory(),

            'anggaran_pelaksanaan' => $this->faker->randomElement(['DIPA', 'NON DIPA']),
            
            // Nama kegiatan khas CFD
            'nama_kegiatan' => 'Sosialisasi P4GN pada Car Free Day ' . $this->faker->city(),
            
            // Tanggal dalam 1 tahun terakhir
            'tanggal_pelaksanaan' => $this->faker->dateTimeBetween('-1 year', 'now'),
            
            // Lokasi (biasanya Alun-alun atau Jalan Protokol)
            'tempat_kegiatan' => 'Alun-alun Kota ' . $this->faker->city() . ', ' . $this->faker->streetName(),
            
            // Jumlah peserta CFD biasanya ramai (50 - 5000)
            'jumlah_peserta' => $this->faker->numberBetween(50, 5000),
        ];
    }
}