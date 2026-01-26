<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\P2mSosialisasi;
use App\Models\SatuanKerja;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\P2mSosialisasi>
 */
class P2mSosialisasiFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = P2mSosialisasi::class;

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
            
            // Pilihan Enum
            'anggaran_pelaksanaan' => $this->faker->randomElement(['DIPA', 'NON DIPA']),
            
            'nama_kegiatan' => $this->faker->sentence(4), // Kalimat acak 4 kata
            
            // Pilihan Enum
            'sasaran_kegiatan' => $this->faker->randomElement([
                'lingkungan pendidikan', 
                'lingkungan kerja', 
                'lingkungan masyarakat',
                'lingkungan swasta'
            ]),
            
            'tanggal_pelaksanaan' => $this->faker->dateTimeBetween('-1 year', 'now'), // Tanggal setahun terakhir
            'tempat_kegiatan' => $this->faker->address(),
            'jumlah_peserta' => $this->faker->numberBetween(10, 500),
        ];
    }
}